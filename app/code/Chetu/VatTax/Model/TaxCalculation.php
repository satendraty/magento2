<?php

/**
 * Copyright © Chetu, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Chetu\VatTax\Model;

use Magento\Tax\Model\Config;
use Magento\Tax\Model\Calculation;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\AppliedTaxInterfaceFactory;
use Magento\Tax\Api\Data\TaxDetailsInterfaceFactory;
use Magento\Tax\Model\Calculation\CalculatorFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Tax\Api\Data\AppliedTaxRateExtensionFactory;
use Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory;
use Magento\Tax\Api\Data\TaxDetailsItemInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemExtensionFactory;
use ClassyLlama\AvaTax\Framework\Interaction\Tax;
use ClassyLlama\AvaTax\Framework\Interaction\Rest\Tax\Result as TaxResult;
use Chetu\VatTax\Helper\Data;

class TaxCalculation extends \ClassyLlama\AvaTax\Framework\Interaction\TaxCalculation
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var AppliedTaxInterfaceFactory
     */
    protected $appliedTaxDataObjectFactory;

    /**
     * @var AppliedTaxRateInterfaceFactory
     */
    protected $appliedTaxRateDataObjectFactory;

    /**
     * @var QuoteDetailsItemExtensionFactory
     */
    protected $extensionFactory;

    /**
     * @var AppliedTaxRateExtensionFactory
     */
    protected $appliedTaxRateExtensionFactory;

    /**
     * Rate that will be used instead of 0, as using 0 causes tax rates to not save
     */
    const DEFAULT_TAX_RATE = -0.001;

    protected $vatTaxHelper;

    /**
     * Constructor
     *
     * @param Calculation                      $calculation
     * @param CalculatorFactory                $calculatorFactory
     * @param Config                           $config
     * @param TaxDetailsInterfaceFactory       $taxDetailsDataObjectFactory
     * @param TaxDetailsItemInterfaceFactory   $taxDetailsItemDataObjectFactory
     * @param StoreManagerInterface            $storeManager
     * @param TaxClassManagementInterface      $taxClassManagement
     * @param DataObjectHelper                 $dataObjectHelper
     * @param PriceCurrencyInterface           $priceCurrency
     * @param AppliedTaxInterfaceFactory       $appliedTaxDataObjectFactory
     * @param AppliedTaxRateInterfaceFactory   $appliedTaxRateDataObjectFactory
     * @param QuoteDetailsItemExtensionFactory $extensionFactory
     * @param AppliedTaxRateExtensionFactory   $appliedTaxRateExtensionFactory
     * @param Data                             $vatTaxHelper
     */
    public function __construct(
        Calculation $calculation,
        CalculatorFactory $calculatorFactory,
        Config $config,
        TaxDetailsInterfaceFactory $taxDetailsDataObjectFactory,
        TaxDetailsItemInterfaceFactory $taxDetailsItemDataObjectFactory,
        StoreManagerInterface $storeManager,
        TaxClassManagementInterface $taxClassManagement,
        DataObjectHelper $dataObjectHelper,
        PriceCurrencyInterface $priceCurrency,
        AppliedTaxInterfaceFactory $appliedTaxDataObjectFactory,
        AppliedTaxRateInterfaceFactory $appliedTaxRateDataObjectFactory,
        QuoteDetailsItemExtensionFactory $extensionFactory,
        AppliedTaxRateExtensionFactory $appliedTaxRateExtensionFactory,
        Data $vatTaxHelper
    ) {
        $this->vatTaxHelper = $vatTaxHelper;
        return parent::__construct(
            $calculation,
            $calculatorFactory,
            $config,
            $taxDetailsDataObjectFactory,
            $taxDetailsItemDataObjectFactory,
            $storeManager,
            $taxClassManagement,
            $dataObjectHelper,
            $priceCurrency,
            $appliedTaxDataObjectFactory,
            $appliedTaxRateDataObjectFactory,
            $extensionFactory,
            $appliedTaxRateExtensionFactory
        );
    }
    /**  
     *
     * @param  QuoteDetailsItemInterface             $item
     * @param  TaxResult                             $getTaxResult
     * @param  bool                                  $useBaseCurrency
     * @param  \Magento\Framework\App\ScopeInterface $scope
     * @return \Magento\Tax\Api\Data\TaxDetailsItemInterface|bool
     */
    protected function getTaxDetailsItem(
        QuoteDetailsItemInterface $item,
        $getTaxResult,
        $useBaseCurrency,
        $scope
    ) {
        $price = $item->getUnitPrice(); 
        /* @var $taxLine \Magento\Framework\DataObject */
        $taxLine = $getTaxResult->getTaxLine($item->getCode()); 
        //  Items that are children of other items won't have lines in the response
        if(is_null($taxLine)) {
            return false;
        }  

         /*Strat custom code  for VAT Calulation*/

           /**
           * if current store is VAT listed then VAT Formula will be apply.
           */
            $storeId = $this->storeManager->getStore()->getId();
            $rate = $getTaxResult->getLineRate($taxLine); 

        if($this->vatTaxHelper->vatCheckedStore($storeId)) {
           
            $tax = ($item->getUnitPrice()- $item->getUnitPrice()/(1 +$rate))*$item->getQuantity();
           
        } else {      
            $tax = (float)$taxLine->getTax();
        }

       
        /* End custom code  for VAT Calulation*/

        /**
         * Magento uses base rates for determining what to charge a customer, not the currency rate (i.e., the non-base
         * rate). Because of this, the base amounts are what is being sent to AvaTax for rate calculation. When we get
         * the base tax amounts back from AvaTax, we have to convert those to the current store's currency using the
         * \Magento\Framework\Pricing\PriceCurrencyInterface::convert() method.
         */

        if (!$useBaseCurrency) {
            $tax = $this->priceCurrency->convert($tax, $scope);
        }
        $rowTax = $tax;
        /**
         * In native Magento, the "row_total_incl_tax" and "base_row_total_incl_tax" fields contain the tax before
         * discount. The AvaTax 15 API doesn't have the concept of before/after discount tax, so in order to determine
         * the "before discount tax amount", we need to multiply the discount by the rate returned by AvaTax.
         *
         * @see \Magento\Tax\Model\Calculation\AbstractAggregateCalculator::calculateWithTaxNotInPrice
         *
         * If the rate is 0, then this product doesn't have taxes applied and tax on discount shouldn't be calculated.
         * If tax is 0, then item was tax-exempt for some reason and tax on discount shouldn't be calculated
         */
        if ($rate > 0 && $tax > 0) {
            /**
             * Accurately calculating what AvaTax would have charged before discount requires checking to see if any
             * of the tax amount is tax exempt. If so, we need to find out what percentage of the total amount AvaTax
             * deemed as taxable and then use that percentage when calculating the discount amount. This partially
             * taxable scenario can arise in a situation like this:
             *
             * @see https://help.avalara.com/kb/001/Why_is_freight_taxed_partially_on_my_sale
             *
             * To test this functionality, you can create a "Base Override" Tax Rule in the AvaTax admin to mark certain
             * jurisdictions as partially taxable.
             */
            $taxableAmountPercentage = 1;
            if ($taxLine->getExemptAmount() > 0) {
                // This value is the total amount sent to AvaTax for tax calculation, before AvaTax determined what
                // portion of the amount is taxable
                $totalAmount = ($taxLine->getTaxableAmount() + $taxLine->getExemptAmount());
                 
                // Avoid division by 0
                if ($totalAmount != 0) {
                    $taxableAmountPercentage = $taxLine->getTaxableAmount() / $totalAmount;
                }
                
            }
            $effectiveDiscountAmount = $taxableAmountPercentage * $item->getDiscountAmount();
            $taxOnDiscountAmount = $effectiveDiscountAmount * $rate;
            $taxOnDiscountAmount = $this->calculationTool->round($taxOnDiscountAmount);
            $rowTaxBeforeDiscount = $rowTax + $taxOnDiscountAmount;
        } else {
            $rowTaxBeforeDiscount = 0;
        }

        $extensionAttributes = $item->getExtensionAttributes();
        if ($extensionAttributes) {
            $quantity = $extensionAttributes->getTotalQuantity() !== null ? $extensionAttributes->getTotalQuantity()
                : $item->getQuantity();
        } else {
            $quantity = $item->getQuantity();
        }
        $rowTotal = $price * $quantity;
        $rowTotalInclTax = $rowTotal + $rowTaxBeforeDiscount;
        $priceInclTax = $rowTotalInclTax / $quantity;

        $discountTaxCompensationAmount = 0;

        /**
         * The \Magento\Tax\Model\Calculation\AbstractAggregateCalculator::calculateWithTaxNotInPrice method that this
         * method is patterned off of has $round as a variable, but any time that method is used in the context of a
         * collect totals on a quote, rounding is always used.
         */

        $round = true;
        if ($round) {
            $priceInclTax = $this->calculationTool->round($priceInclTax);
        }
        return $this->taxDetailsItemDataObjectFactory->create()
            ->setCode($item->getCode())
            ->setType($item->getType())
            ->setRowTax($rowTax)
            ->setPrice($price)
            ->setPriceInclTax($priceInclTax)
            ->setRowTotal($rowTotal)
            ->setRowTotalInclTax($rowTotalInclTax)
            ->setDiscountTaxCompensationAmount($discountTaxCompensationAmount)
            ->setAssociatedItemCode($item->getAssociatedItemCode())
            ->setTaxPercent($rate * Tax::RATE_MULTIPLIER)
            ->setAppliedTaxes($this->getAppliedTaxes($taxLine, $useBaseCurrency, $scope));
    }
}
