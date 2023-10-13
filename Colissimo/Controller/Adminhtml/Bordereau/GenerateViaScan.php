<?php
/**
 * ******************************************************
 *  * Copyright (C) 2018 La Poste.
 *  *
 *  * This file is part of La Poste - Colissimo module.
 *  *
 *  * La Poste - Colissimo module can not be copied and/or distributed without the express
 *  * permission of La Poste.
 *  ******************************************************
 *
 */

namespace LaPoste\Colissimo\Controller\Adminhtml\Bordereau;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class GenerateViaScan to display scan form
 * @package LaPoste\Colissimo\Controller\Adminhtml\Bordereau
 */
class GenerateViaScan extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * BordereauViaScan constructor.
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('LaPoste_Colissimo::bordereau');
        $resultPage->getConfig()->getTitle()->prepend(__('Generate bordereau'));

        return $resultPage;
    }

}
