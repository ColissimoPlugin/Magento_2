<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Prices;

use LaPoste\Colissimo\Model\Carrier\Colissimo;

class Edit extends \LaPoste\Colissimo\Controller\Adminhtml\Prices
{

    protected $resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context        $context
     * @param \Magento\Framework\Registry                $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Edit action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('entity_id');
        $model = $this->_objectManager->create(\LaPoste\Colissimo\Model\Prices::class);

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This Prices no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->_coreRegistry->register('laposte_colissimo_prices', $model);

        // 3. Build edit form
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Price') : __('New Price'),
            $id ? __('Edit Price') : __('New Price')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Prices'));
        if ($model->getId()) {
            $methods = Colissimo::METHODS_CODES_TRANSLATIONS;
            $methodName = __($methods[$model->getMethod()]);
            $title = __('Edit Price %1 - %2', $methodName, $model->getArea());
        } else {
            $title = __('New Price');
        }

        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}

