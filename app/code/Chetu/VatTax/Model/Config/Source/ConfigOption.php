<?php
/**
 * Copyright © Chetu, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Chetu\VatTax\Model\Config\Source;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\OptionSourceInterface;

class ConfigOption implements OptionSourceInterface
{

    /**
     * @var Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManagerInterface;

    public function __construct(
        StoreManagerInterface $storeManagerInterface
    ) {
        $this->_storeManagerInterface = $storeManagerInterface;
    }

    /**
     * Store list return function
     *
     * @return array
     */
    public function toOptionArray()
    {
        $storeManagerDataList = $this->_storeManagerInterface->getStores();
        $options = array();
        foreach ($storeManagerDataList as $key => $value) {
            $options[] = ['label' => $value['name'],  'value' => $key];
        }
        return $options;
    }
}
