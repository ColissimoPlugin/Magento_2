<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Controller\BalReturn;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Confirm extends Action
{
    protected $pageFactory;
    protected $helperData;

    public function __construct(
        Context $context,
        \LaPoste\Colissimo\Helper\Data $helperData,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        parent::__construct($context);
        $this->helperData = $helperData;
        $this->pageFactory = $pageFactory;
    }

    public function execute()
    {
        if (!$this->helperData->getAdvancedConfigValue('lpc_bal/allowMailBoxPickUp')) {
            return $this->getResponse()
                        ->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_INTERNAL_ERROR)
                        ->setContent(__('MailBox PickUp is not enabled!'));
        }

        return $this->pageFactory->create();
    }
}
