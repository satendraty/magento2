<?php
namespace ChetuTest\WorldClassSoftware\Model\ResourceModel;

/**
 * Class Thing
 */
class Thing extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Init
     */
    protected function _construct() // phpcs:ignore PSR2.Methods.MethodDeclaration
    {
        $this->_init('chetutest_worldclasssoftware_thing', 'thing_id');
    }
}
