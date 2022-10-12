<?php

/**
 * Totals file override for update invoice. 
 * Paid and Refended price as per vat selected country.
 */

namespace Chetu\VatTax\Block\Adminhtml\Order;
use Chetu\VatTax\Helper\Data;

class TotalsData extends \Magento\Sales\Block\Adminhtml\Order\Totals
{
    /**
     * Initialize order totals array
     *
     * @return $this
     */
    protected $helper;

    /**
     * Admin helper
     *
     * @var \Magento\Sales\Helper\Admin
     */
    protected $_adminHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        Data $helper
    ) {
        $this->helper = $helper;
        parent::__construct($context, $registry, $adminHelper, []);
           
    }
    protected function _initTotals()
    {
        parent::_initTotals();
        $this->_totals['paid'] = new \Magento\Framework\DataObject(
            [
                'code' => 'paid',
                'strong' => true,
                'value' => $this->updatedPaidDue(),
                'base_value' => $this->updatedPaidDue(),
                'label' => __('Total Paid'),
                'area' => 'footer',
            ]
        );
        $this->_totals['refunded'] = new \Magento\Framework\DataObject(
            [
                'code' => 'refunded',
                'strong' => true,
                'value' => $this->updatedTotalRefunded(),
                'base_value' => $this->updatedTotalRefunded(),
                'label' => __('Total Refunded'),
                'area' => 'footer',
            ]
        );
            $code = 'due';
            $label = 'Total Due';

            $value = $this->getSource()->getTotalDue();
            $baseValue = $this->getSource()->getBaseTotalDue();
        if($this->getSource()->getTotalCanceled() > 0 && $this->getSource()->getBaseTotalCanceled() > 0) {
            $code = 'canceled';
            $label = 'Total Canceled';
            $value = $this->getSource()->getTotalCanceled();
            $baseValue = $this->getSource()->getBaseTotalCanceled();
        }
        $this->_totals[$code] = new \Magento\Framework\DataObject(
            [
                'code' => 'due',
                'strong' => true,
                'value' => $value,
                'base_value' => $baseValue,
                'label' => __($label),
                'area' => 'footer',
            ]
        );
        return $this;
    }
    
    /**
     * Total Updated Paid Due Function
     *
     * @return int 
     */
    protected function updatedPaidDue()
    { 
        return $this->getSource()->getTotalPaid();
        
    }
    protected function updatedTotalDue()
    { 
        
        if($this->getSource()->getTotalPaid() > 0 && $this->getOrderId()) {
            return $this->getSource()->getTotalPaid() - $this->getSource()->getTaxAmount();
        } else {         
            return $this->getSource()->getTotalPaid();
        }
    }
    protected function updatedTotalRefunded()
    { 
        return $this->getSource()->getTotalRefunded();
    }

    /**
     * Order Id Function
     *
     * @return int
     */
    protected function getOrderId()
    {
        $orderId = $this->getRequest()->getParam('order_id'); 
        return $this->helper->checkStore($orderId);
    }

}
