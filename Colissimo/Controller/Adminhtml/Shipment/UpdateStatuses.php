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

use LaPoste\Colissimo\Api\UnifiedTrackingApi;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class UpdateStatuses extends Action
{
    /**
     * @var \LaPoste\Colissimo\Api\UnifiedTrackingApi
     */
    protected $unifiedTrackingApi;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * UpdateStatuses constructor.
     * @param \Magento\Backend\App\Action\Context                  $context
     * @param \LaPoste\Colissimo\Api\UnifiedTrackingApi            $unifiedTrackingApi
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     */
    public function __construct(
        Context $context,
        UnifiedTrackingApi $unifiedTrackingApi,
        RemoteAddress $remoteAddress
    ) {
        parent::__construct($context);
        $this->unifiedTrackingApi = $unifiedTrackingApi;
        $this->remoteAddress = $remoteAddress;
    }


    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $result = $this->unifiedTrackingApi->updateAllStatuses(null, null, $this->remoteAddress->getRemoteAddress(), null);
        $failure = $result['failure'];

        if (empty($failure)) {
            $this->messageManager->addSuccessMessage(__('All statuses where updated'));
        } else {
            $this->messageManager->addErrorMessage(__('Some status were not correctly updated. Check logs for more details.'));
        }

        return $this->_redirect('laposte_colissimo/shipment/index');
    }
}
