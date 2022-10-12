<?php
namespace ChetuTest\WorldClassSoftware\Controller\Adminhtml\Thing;


/**
 * Class Index
 */
class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'ChetuTest_WorldClassSoftware::things';
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/index/index');
        return $resultRedirect;
    }
}
