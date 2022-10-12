<?php

/**
 * Copyright © Chetu, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Chetu\VatTax\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Chetu\VatTax\Helper\Data;

class OrderObserver implements ObserverInterface
{
    /**
     * @var helperData
     */
    protected $_helperData;

    /**
     *
     * @param Data $helperData
     */
    public function __construct(
        Data $helperData
    ) {
        $this->_helperData = $helperData;
    }

    /**
     * index function
     *
     * @param  Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if($this->_helperData->vatCheckedStore($order->getStoreId())) {           
            $orderDiscount = ($order->getBaseDiscountAmount() > 0) ? $order->getBaseDiscountAmount() : 0;
            if($order->getGrandTotal() == ($order->getSubTotal() + $order->getTaxAmount() + $order->getShippingAmount() + $orderDiscount)) {
                $order->setBaseGrandTotal($order->getBaseGrandTotal() - $order->getTaxAmount() + $orderDiscount);
                $order->setGrandTotal($order->getBaseGrandTotal());
             }        
             if($order->getBaseDiscountAmount() < 0){
                $order->setBaseGrandTotal($order->getBaseGrandTotal() - $order->getTaxAmount() + $orderDiscount);
                $order->setGrandTotal($order->getBaseGrandTotal());   
             }
            $order->setBaseTotalDue($order->getBaseTotalDue() + $order->getTaxAmount());
            $order->setTotalDue($order->getBaseTotalDue());
        }
        return $this;
    }
}
