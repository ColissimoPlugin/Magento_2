<?php
/**
 * ******************************************************
 *  * Copyright (C) 2023 La Poste.
 *  *
 *  * This file is part of La Poste - Colissimo module.
 *  *
 *  * La Poste - Colissimo module can not be copied and/or distributed without the express
 *  * permission of La Poste.
 *  ******************************************************
 *
 */

namespace LaPoste\Colissimo\Controller\Adminhtml\Prices;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Import extends Action
{
    const ADMIN_RESOURCE = 'LaPoste_Colissimo::top_level';

    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE)
                   ->addBreadcrumb(__('LaPoste'), __('LaPoste'))
                   ->addBreadcrumb(__('Prices'), __('Prices'))
                   ->addBreadcrumb(__('Import'), __('Import'));
        $resultPage->getConfig()->getTitle()->prepend(__('Prices import'));

        return $resultPage;
    }
}
