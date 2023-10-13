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

namespace LaPoste\Colissimo\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use LaPoste\Colissimo\Model\AccountApi;

class Index extends \Magento\Sales\Controller\Adminhtml\Shipment\AbstractShipment\Index
{
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        AccountApi $accountApi
    ) {
        parent::__construct($context, $resultPageFactory);

        if (!$accountApi->isCgvAccepted()) {
            $context->getMessageManager()->addComplexErrorMessage('colissimoCgv');
        }
    }
}
