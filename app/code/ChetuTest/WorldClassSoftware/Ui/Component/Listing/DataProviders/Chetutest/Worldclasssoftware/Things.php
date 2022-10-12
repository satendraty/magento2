<?php
namespace ChetuTest\WorldClassSoftware\Ui\Component\Listing\DataProviders\Chetutest\Worldclasssoftware;


/**
 * Class Things
 */
class Things extends \Magento\Ui\DataProvider\AbstractDataProvider
{    
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \ChetuTest\WorldClassSoftware\Model\ResourceModel\Thing\CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }
}
