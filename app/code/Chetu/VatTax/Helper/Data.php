<?php
/**
 * Copyright © Chetu, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * Data.php
 *
 * @author    Chetu Inc <developer@chetu.com>
 * @copyright 2022 Chetu, Inc.
 * @license   Licence Name
 * @see       Link to project website
 */

namespace Chetu\VatTax\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const VATTAX_STORE_SELECT = "vattax/general/store_select";

    /**
     * @var Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @var Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var Magento\Framework\Module\Manager $moduleManager
     */
    protected $_moduleManager;
    /**
     * @var Magento\Framework\Module\Manager $moduleManager
     */
    protected $orderRepository;

    /**
     * @var Magento\Sales\Model\Order\InvoiceFactory $invoiceFactory
     */
    protected $invoiceFactory;
    
    /**
     * @var \Magento\Sales\Model\Order\CreditmemoRepository
     */
    protected $сreditmemo;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\CreditmemoRepository $сreditmemo,
        \Magento\Sales\Model\Order\InvoiceFactory $invoiceFactory
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->_moduleManager = $moduleManager;
        $this->orderRepository = $orderRepository;
        $this->сreditmemo = $сreditmemo;
        $this->invoiceFactory = $invoiceFactory;
    }

    /**
     * GetOrder Id function
     *
     * @param  [int] $id
     * @return int
     */
    public function getOrder($id)
    {
        return $this->orderRepository->get($id)->getStoreId();
    }
    /**
     * getOrderByInvoice function
     *
     * @param  [type] $invoiceId
     * @return void
     */
    public function getOrderByInvoice($invoiceId)
    {

        $invoice = $this->invoiceFactory->create()->load($invoiceId);
        return $invoice->getOrder()->getId();
    }
    /**
     * getCurrentStoreId function
     *
     * @return int
     */
    public function getCurrentStoreId()
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $storeId;
    }
    /**
     * isModuleEnable function
     * get module status function
     *
     * @return boolean
     */
    public function isModuleEnable()
    {
        return ($this->_moduleManager->isEnabled('ClassyLlama_AvaTax') && $this->_moduleManager->isEnabled('Chetu_VatTax')) ? 1 : 0;
    }

    /**
     * Check Store Function
     *
     * @return int
     */

    public function checkStore($orderId)
    {
        if ($this->isModuleEnable() && $orderId != null) {
            $valueFromConfig = $this->scopeConfig->getValue(self::VATTAX_STORE_SELECT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->getOrder($orderId));
            $storeIdArray = explode(',', $valueFromConfig);
            return (in_array($this->getOrder($orderId), $storeIdArray)) ? 1 : 0;
        } else {
            return 0;
        }
    }

    /**
     *  Get Order Id by Creditmemo id getCreditOrderId function
     * 
     * @return int
     */
    public function getCreditOrderId($creditId = null)
    {
        $orderId = $this->сreditmemo->get($creditId)->getOrderId();
        return $orderId;
    }

    /**
     * CheckCreditStore function
     * 
     * @param [int]  creditmemoId
     * 
     * @return int
     */

    public function checkCreditStore($creditmemoId)
    {
        if ($this->isModuleEnable() && $creditmemoId != null) {
            $valueFromConfig = $this->scopeConfig->getValue(self::VATTAX_STORE_SELECT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->getOrder($this->getCreditOrderId($creditmemoId)));
            $storeIdArray = explode(',', $valueFromConfig);
            return (in_array($this->getOrder($this->getCreditOrderId($creditmemoId)), $storeIdArray)) ? 1 : 0;
        } else {
            return 0;
        }
    }

    /**
     * Check VAT Store function
     *
     * @return int
     */

    public function vatCheckedStore($storeId)
    {
        if ($this->isModuleEnable() && $storeId != null) {
            $valueFromConfig = $this->scopeConfig->getValue(self::VATTAX_STORE_SELECT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
            $vatStoreId = explode(',', $valueFromConfig);
            return (in_array($storeId, $vatStoreId)) ? 1 : 0;
        } else {
            return 0;
        }
    }

    /**
     * Check Invoice Store Function 
     * 
     * @return int
     */
    public function checkInvoiceStore($invoiceId)
    {
        if ($this->isModuleEnable() && $invoiceId != null) {
            $valueFromConfig = $this->scopeConfig->getValue(self::VATTAX_STORE_SELECT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->getOrder($this->getOrderByInvoice($invoiceId)));
            $storeIdArray = explode(',', $valueFromConfig);
            return (in_array($this->getOrder($this->getOrderByInvoice($invoiceId)), $storeIdArray)) ? 1 : 0;
        } else {
            return 0;
        }
    }

    /**
     * This function returns the StoreCodes checkStoreForFrontend function
     *
     * @return array
     */
    public function checkStoreForFrontend()
    {
        if ($this->isModuleEnable()) {
            $valueFromConfig = $this->scopeConfig->getValue(self::VATTAX_STORE_SELECT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getStoreId());
            if (!is_null($valueFromConfig)) {
                $storeIdArray = explode(',', $valueFromConfig);
                $storeCodes=[];
                if ($storeIdArray && is_array($storeIdArray) && count($storeIdArray)>0) {
                    foreach ($storeIdArray as $storear) {                   
                        $storeData = $this->storeManager->getStore($storear);
                        $storeCodes[] = (string)$storeData->getCode();
                    }
                    return $storeCodes;
                } else {
                    return [];
                }
            } else {
                return [];
            }
        } else {
            return [];
        }
    }
}