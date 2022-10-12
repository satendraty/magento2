<?php
namespace ChetuTest\WorldClassSoftware\Controller\Adminhtml\Thing;

use Magento\Backend\App\Action;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
            

/**
 * Class Save
 */
class Save extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'ChetuTest_WorldClassSoftware::things';

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var \ChetuTest\WorldClassSoftware\Model\ThingRepository
     */
    protected $objectRepository;

    /**
     * @param Action\Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param \ChetuTest\WorldClassSoftware\Model\ThingRepository $objectRepository
     */
    public function __construct(
        Action\Context $context,
        DataPersistorInterface $dataPersistor,
        \ChetuTest\WorldClassSoftware\Model\ThingRepository $objectRepository
    ) {
        $this->dataPersistor    = $dataPersistor;
        $this->objectRepository  = $objectRepository;

        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            if (isset($data['is_active']) && $data['is_active'] === 'true') {
                $data['is_active'] = ChetuTest\WorldClassSoftware\Model\Thing::STATUS_ENABLED;
            }
            if (empty($data['thing_id'])) {
                $data['thing_id'] = null;
            }

            /** @var \ChetuTest\WorldClassSoftware\Model\Thing $model */
            $model = $this->_objectManager->create('ChetuTest\WorldClassSoftware\Model\Thing');

            $id = $this->getRequest()->getParam('thing_id');
            if ($id) {
                $model = $this->objectRepository->getById($id);
            }

            $model->setData($data);

            try {
                $this->objectRepository->save($model);
                $this->messageManager->addSuccess(__('You saved the thing.'));
                $this->dataPersistor->clear('chetutest_worldclasssoftware_thing');
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['thing_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the data.'));
            }

            $this->dataPersistor->set('chetutest_worldclasssoftware_thing', $data);
            return $resultRedirect->setPath('*/*/edit', ['thing_id' => $this->getRequest()->getParam('thing_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
