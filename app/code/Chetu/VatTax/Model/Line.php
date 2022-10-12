<?php

/**
 * Copyright © Chetu, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Chetu\VatTax\Model;

use Magento\Framework\DataObjectFactory;
use ClassyLlama\AvaTax\Framework\Interaction\MetaData\MetaDataObjectFactory;
use ClassyLlama\AvaTax\Helper\Config;
use ClassyLlama\AvaTax\Framework\Interaction\MetaData\ValidationException;
use ClassyLlama\AvaTax\Helper\CustomsConfig;
use ClassyLlama\AvaTax\Helper\TaxClass;
use ClassyLlama\AvaTax\Model\Logger\AvaTaxLogger;
use Chetu\VatTax\Helper\Data;

class Line extends \ClassyLlama\AvaTax\Framework\Interaction\Line
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \ClassyLlama\AvaTax\Helper\TaxClass
     */
    protected $taxClassHelper;

    /**
     * @var \ClassyLlama\AvaTax\Model\Logger\AvaTaxLogger
     */
    protected $avaTaxLogger;

    /**
     * @var DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var CustomsConfig
     */
    protected $customsConfigHelper;


    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var MetaData\MetaDataObject|null
     */
    protected $metaDataObject = null;

    /**
     * Index that will be incremented for AvaTax line numbers
     *
     * @var int
     */
    protected $lineNumberIndex = 0;

    /**
     * Class constructor
     *
     * @param Config                                        $config
     * @param \ClassyLlama\AvaTax\Helper\TaxClass           $taxClassHelper
     * @param \ClassyLlama\AvaTax\Model\Logger\AvaTaxLogger $avaTaxLogger
     * @param MetaDataObjectFactory                         $metaDataObjectFactory
     * @param DataObjectFactory                             $dataObjectFactory
     * @param CustomsConfig                                 $customsConfigHelper
     */
    public function __construct(
        Config $config,
        TaxClass $taxClassHelper,
        AvaTaxLogger $avaTaxLogger,
        MetaDataObjectFactory $metaDataObjectFactory,
        DataObjectFactory $dataObjectFactory,
        CustomsConfig $customsConfigHelper,
        Data $helperData
    ) {
        $this->config = $config;
        $this->taxClassHelper = $taxClassHelper;
        $this->avaTaxLogger = $avaTaxLogger;
        $this->metaDataObject = $metaDataObjectFactory->create(['metaDataProperties' => $this::$validFields]);
        $this->dataObjectFactory = $dataObjectFactory;
        $this->customsConfigHelper = $customsConfigHelper;
        $this->_helperData = $helperData;
    }

    /**
     * Return an array with relevant data from an invoice item
     *
     * @param  \Magento\Sales\Api\Data\InvoiceItemInterface $item
     * @return \Magento\Framework\DataObject|bool
     */
    protected function convertInvoiceItemToData(\Magento\Sales\Api\Data\InvoiceItemInterface $item)
    {
        if (!$this->isProductCalculated($item->getOrderItem())) {
            return false;
        }

        // The AvaTax 15 API doesn't support the concept of line-based discounts, so subtract discount amount
        // from taxable amount

        $storeId=$this->_helperData->getCurrentStoreId();

        if($this->_helperData->vatCheckedStore($storeId)) {
            $amount = $item->getBaseRowTotal() - $item->getBaseDiscountAmount() - $item->getTaxAmount();
        }else{
            $amount = $item->getBaseRowTotal() - $item->getBaseDiscountAmount();
        }

        if ($item->getQty() == 0) {
            return false;
        }

        $storeId = $item->getInvoice()->getStoreId();
        $product = $item->getOrderItem()->getProduct();
        $itemData = $this->buildItemData($product, $storeId);

        if (!$itemData['itemCode']) {
            $itemData['itemCode'] = $item->getSku();
        }
        $taxIncluded = false;
        $data = [
            'store_id' => $storeId,
            'mage_sequence_no' => $this->getLineNumber(),
            'item_code' => $itemData['itemCode'],
            'tax_code' => $itemData['taxCode'],
            'description' => $item->getName(),
            'quantity' => $item->getQty(),
            'amount' => $amount,
            'tax_included' => $taxIncluded,
            'ref_1' => $itemData['productRef1'],
            'ref_2' => $itemData['productRef2']
        ];

        $extensionAttributes = $item->getExtensionAttributes();
        if ($this->customsConfigHelper->enabled() && $extensionAttributes) {
            if ($extensionAttributes->getHsCode() !== null) {
                $data['hs_code'] = $extensionAttributes->getHsCode();
            }
            if ($extensionAttributes->getUnitName() !== null) {
                $data['unit_name'] = $extensionAttributes->getUnitName();
            }
            if ($extensionAttributes->getUnitAmount() !== null) {
                $data['unit_amount'] = $extensionAttributes->getUnitAmount();
            }
            if ($extensionAttributes->getPrefProgramIndicator() !== null) {
                $data['preference_program'] = $extensionAttributes->getPrefProgramIndicator();
            }
        }

        /**
 * @var \Magento\Framework\DataObject $line 
*/
        $line = $this->dataObjectFactory->create(['data' => $data]);

        return $line;
    }

    /**
     * Return an array with relevant data from an credit memo item
     *
     * @param  \Magento\Sales\Api\Data\CreditmemoItemInterface $item
     * @return \Magento\Framework\DataObject|bool
     */
    protected function convertCreditMemoItemToData(\Magento\Sales\Api\Data\CreditmemoItemInterface $item)
    {
        if(!$this->isProductCalculated($item->getOrderItem())) {
            return false;
        }

        // The AvaTax 15 API doesn't support the concept of line-based discounts, so subtract discount amount
        // from taxable amount
        $amount = $item->getBaseRowTotal() - $item->getBaseDiscountAmount() - $item->getBaseTaxAmount();
        // Credit memo amounts need to be sent to AvaTax as negative numbers
        $amount *= -1;
        if ($item->getQty() == 0) {
            return false;
        }

        $storeId = $item->getCreditmemo()->getStoreId();
        $product = $item->getOrderItem()->getProduct();
        $itemData = $this->buildItemData($product, $storeId);

        if (!$itemData['itemCode']) {
            $itemData['itemCode'] = $item->getSku();
        }

        $taxIncluded = false;  

        $data = [
            'store_id' => $storeId,
            'mage_sequence_no' => $this->getLineNumber(),
            'item_code' => $itemData['itemCode'],
            'tax_code' => $itemData['taxCode'],
            'description' => $item->getName(),
            'quantity' => $item->getQty(),
            'amount' => $amount,
            'tax_included' => $taxIncluded,
            'ref_1' => $itemData['productRef1'],
            'ref_2' => $itemData['productRef2']
        ];

        $extensionAttributes = $item->getExtensionAttributes();
        if ($this->customsConfigHelper->enabled() && $extensionAttributes) {
            if ($extensionAttributes->getHsCode() !== null) {
                $data['hs_code'] = $extensionAttributes->getHsCode();
            }
            if ($extensionAttributes->getUnitName() !== null) {
                $data['unit_name'] = $extensionAttributes->getUnitName();
            }
            if ($extensionAttributes->getUnitAmount() !== null) {
                $data['unit_amount'] = $extensionAttributes->getUnitAmount();
            }
            if ($extensionAttributes->getPrefProgramIndicator() !== null) {
                $data['preference_program'] = $extensionAttributes->getPrefProgramIndicator();
            }
        }

        /**
 * @var \Magento\Framework\DataObject $line 
*/
        $line = $this->dataObjectFactory->create(['data' => $data]);

        return $line;
    }

    /**
     * Convert \Magento\Tax\Model\Sales\Quote\ItemDetails to an array to be used for building a line object
     *
     * @param  \Magento\Tax\Api\Data\QuoteDetailsItemInterface $item
     * @return \Magento\Framework\DataObject
     */
    protected function convertTaxQuoteDetailsItemToData(\Magento\Tax\Api\Data\QuoteDetailsItemInterface $item)
    {
        $extensionAttributes = $item->getExtensionAttributes();
        if ($extensionAttributes) {
            $quantity = $extensionAttributes->getTotalQuantity() !== null
                ? $extensionAttributes->getTotalQuantity()
                : $item->getQuantity();
        } else {
            $quantity = $item->getQuantity();
        }
        $itemCode = $extensionAttributes ? $extensionAttributes->getAvataxItemCode() : '';
        $description = $extensionAttributes ? $extensionAttributes->getAvataxDescription() : '';
        $taxCode = $extensionAttributes ? $extensionAttributes->getAvataxTaxCode() : null;
        // Calculate tax with or without discount based on config setting

        if ($this->config->getCalculateTaxBeforeDiscount($item->getStoreId())) {
            $amount = ($item->getUnitPrice() * $quantity) - $item->getBaseTaxAmount();
        } else {
            $amount = ($item->getUnitPrice() * $quantity) - ($item->getDiscountAmount() );
        }
        $ref1 = $extensionAttributes ? $extensionAttributes->getAvataxRef1() : null;
        $ref2 = $extensionAttributes ? $extensionAttributes->getAvataxRef2() : null;
        $taxIncluded = false;
        
        $data = [
            'mage_sequence_no' => $item->getCode(),
            'item_code' => $itemCode,
            'tax_code' => $taxCode,
            'description' => $description,
            'quantity' => $item->getQuantity(),
            'amount' => $amount,
            'tax_included' => $taxIncluded,
            'ref_1' => $ref1,
            'ref_2' => $ref2,
        ];
        if ($this->customsConfigHelper->enabled() && $extensionAttributes) {
            if ($extensionAttributes->getHsCode() !== null) {
                $data['hs_code'] = $extensionAttributes->getHsCode();
            }
            if ($extensionAttributes->getUnitName() !== null) {
                $data['unit_name'] = $extensionAttributes->getUnitName();
            }
            if ($extensionAttributes->getUnitAmount() !== null) {
                $data['unit_amount'] = $extensionAttributes->getUnitAmount();
            }
            if ($extensionAttributes->getPrefProgramIndicator() !== null) {
                $data['preference_program'] = $extensionAttributes->getPrefProgramIndicator();
            }
        }
        /**
 * @var \Magento\Framework\DataObject $line 
*/
        $line = $this->dataObjectFactory->create(['data' => $data]);
        return $line;
    }

    /**
     * Get tax line object
     *
     * @param  $data
     * @return \Magento\Framework\DataObject|null|bool
     */
    public function getLine($data)
    {
        /**
 * @var \Magento\Framework\DataObject $line 
*/
        $line = false;
        
        switch (true) {
        case ($data instanceof \Magento\Tax\Api\Data\QuoteDetailsItemInterface):
            $line = $this->convertTaxQuoteDetailsItemToData($data);
            break;
        case ($data instanceof \Magento\Sales\Api\Data\InvoiceItemInterface):
            $line = $this->convertInvoiceItemToData($data);
            break;
        case ($data instanceof \Magento\Sales\Api\Data\CreditmemoItemInterface):
            $line = $this->convertCreditMemoItemToData($data);
            break;
        case (!is_array($data)):
            return false;
                break;
        }
        if (!$line) {
            return null;
        }
        try {
            $validatedData = $this->metaDataObject->validateData($line->getData());
            $line->setData($validatedData);
        } catch (ValidationException $e) {
            $this->avaTaxLogger->error(
                'Error validating line: ' . $e->getMessage(), [
                'data' => var_export($line->getData(), true)
                ]
            );
        }
        return $line;
    }

    /**
     * Accepts an invoice or creditmemo and returns a line object
     *
     * @param  \Magento\Sales\Api\Data\InvoiceInterface|\Magento\Sales\Api\Data\CreditmemoInterface $data
     * @param  $credit
     * @return \Magento\Framework\DataObject|bool
     * @throws ValidationException
     */
    public function getShippingLine($data, $credit)
    { 
        $shippingAmount = $data->getBaseShippingAmount();
        // If shipping rate doesn't have cost associated with it, do nothing
        if ($shippingAmount <= 0) {
            return false;
        }
        //Check the order to see if a shipping discount amount exists
        //and the shipping amount on the invoice|creditmemo matches the shipping amount on the order
        //then subtract the discount amount from the shipping amount and if 0 return false

        $shippingDiscountAmount = $data->getOrder()->getShippingDiscountAmount();
        $orderShippingAmount = $data->getOrder()->getShippingAmount();
        $shippingTaxAmount = $data->getBaseShippingTaxAmount();
        if($shippingDiscountAmount > 0 && $shippingAmount == $orderShippingAmount
            && $shippingAmount - $shippingDiscountAmount >= 0
        ) {

            /* VAT Custom code started */  
            /**
             * Here Checked condition IF current store is VAT list apply then shipping tax will be dedected.
             */
           
            if($this->_helperData->vatCheckedStore($data->getStoreId())) {
                $shippingAmount = $shippingAmount - $shippingTaxAmount - $shippingDiscountAmount;
            } else {
                $shippingAmount = $shippingAmount - $shippingDiscountAmount;
            }
        }
        if ($credit) {
            $shippingAmount *= -1;
        }
        $storeId = $data->getStoreId();
        $itemCode = $this->config->getSkuShipping($storeId);
        $data = [
            'mage_sequence_no' => $this->getLineNumber(),
            'item_code' => $itemCode,
            'tax_code' => $this->taxClassHelper->getAvataxTaxCodeForShipping(),
            'description' => self::SHIPPING_LINE_DESCRIPTION,
            'quantity' => $data->getTotalQty(),
            'amount' => $shippingAmount,
        ];
        /* VAT Custom code ended */
         $line = $this->dataObjectFactory->create(['data' => $data]);
        try {
            $validatedData = $this->metaDataObject->validateData($line->getData());
            $line->setData($validatedData);
        } catch (ValidationException $e) {
            $this->avaTaxLogger->error(
                'Error Validating Line: ' . $e->getMessage(), [
                'data' => var_export($line->getData(), true)
                ]
            );
            throw $e;
        }
        return $line;
    }
}
