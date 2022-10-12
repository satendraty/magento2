<?php
namespace ChetuTest\WorldClassSoftware\Model;

/**
 * Class Thing
 */
class Thing extends \Magento\Framework\Model\AbstractModel implements
    \ChetuTest\WorldClassSoftware\Api\Data\ThingInterface,
    \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'chetutest_worldclasssoftware_thing';

    /**
     * Init
     */
    protected function _construct() // phpcs:ignore PSR2.Methods.MethodDeclaration
    {
        $this->_init(\ChetuTest\WorldClassSoftware\Model\ResourceModel\Thing::class);
    }

    /**
     * @inheritDoc
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
