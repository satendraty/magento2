<?php

/**
 * Copyright © Chetu, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Chetu\VatTax\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\LayoutInterface;
use Chetu\VatTax\Helper\Data;
use  Magento\Store\Model\StoreManagerInterface;

/**
 * ConfigProvider : This Class is used to set config data to checkout 
 */
class ConfigProvider implements ConfigProviderInterface
{
   
    /**
     *
     * @var HelperData
     */
    protected $_helperData;
    
    public function __construct(
        Data $helperData
    ) {
        $this->_helperData = $helperData;
    }

    /**
     * Store check function
     *
     * @return []
     */
    public function getConfig()
    {
        $additionalVariables = [];
        $storeCodes=$this->_helperData->checkStoreForFrontend();
        if ($storeCodes && !empty($storeCodes) && is_array($storeCodes) && count($storeCodes)>0) {
            $additionalVariables['scope_var'] = implode(",", $storeCodes);
        } else {
            $additionalVariables['scope_var'] = "";
        }
        
        return $additionalVariables;
    }
}
