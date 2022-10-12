<?php

/**
 * This file override for shipping price inclusive price display for as per vat selected country.
 */

namespace Chetu\VatTax\Block\Adminhtml\Order;

use Magento\Sales\Model\Order;
use Magento\Framework\App\ObjectManager;
use Magento\Shipping\Helper\Data as ShippingHelper;
use Magento\Tax\Helper\Data as TaxHelper;
use Chetu\VatTax\Helper\Data as VatHelperData;

/**
 * Adminhtml order abstract block
 *
 * @api
 * @author Magento Core Team <core@magentocommerce.com>
 * @since  100.0.2
 */

class AbstractOrder extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /**
     * @var CoreRegistry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Sales\Helper\Admin
     */
    protected $_adminHelper;

    /**
     * @var VatHelperData
     */
    protected $_vatHelperData;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Sales\Helper\Admin             $adminHelper
     * @param \Magento\Framework\Registry             $registry
     * @param array                                   $data
     * @param ShippingHelper|null                     $shippingHelper
     * @param TaxHelper|null                          $taxHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        VatHelperData $vatHelperData,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Framework\Registry $registry,
        array $data = [],
        ?ShippingHelper $shippingHelper = null,
        ?TaxHelper $taxHelper = null
    ) {
        $this->_vatHelperData = $vatHelperData;
        $data['shippingHelper'] = $shippingHelper ?? ObjectManager::getInstance()->get(ShippingHelper::class);
        $data['taxHelper'] = $taxHelper ?? ObjectManager::getInstance()->get(TaxHelper::class);
        parent::__construct($context, $registry, $adminHelper, $data);
    }
    /**
     * Shipping price display with Inclusive function
     * 
     * @return int || flote
     */
    public function displayShippingPriceInclTax($order)
    {
        $shipping = $order->getShippingInclTax();
      
        /*CUstom VAT Code Start*/
        if($shipping) {         
            if($this->_vatHelperData->vatCheckedStore($order->getStoreId())) {
                $baseShipping = $order->getBaseShippingInclTax() - $order->getShippingTaxAmount();
                $shipping = $order->getBaseShippingInclTax() - $order->getShippingTaxAmount();

            }else {  

                $baseShipping = $order->getBaseShippingInclTax();
            }
        
        } else {
                
            $shipping = $order->getShippingAmount() + $order->getShippingTaxAmount();
            $baseShipping = $order->getBaseShippingAmount() + $order->getBaseShippingTaxAmount();
        }
        /*CUstom VAT Code End*/
        return $this->displayPrices($baseShipping, $shipping, false, ' ');
    }
}
