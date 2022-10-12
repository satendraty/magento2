<?php
namespace ChetuTest\WorldClassSoftware\Model\ResourceModel\Thing;

/**
 * Class Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Init
     */
    protected function _construct() // phpcs:ignore PSR2.Methods.MethodDeclaration
    {
        $this->_init(
            \ChetuTest\WorldClassSoftware\Model\Thing::class,
            \ChetuTest\WorldClassSoftware\Model\ResourceModel\Thing::class
        );
    }
}
