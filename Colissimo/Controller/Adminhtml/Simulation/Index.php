<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Simulation;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'LaPoste_Colissimo::simulation';

    protected PageFactory $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('LaPoste_Colissimo::simulation');
        $resultPage->getConfig()->getTitle()->prepend(__('Colissimo Price simulation'));

        return $resultPage;
    }
}
