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
use LaPoste\Colissimo\Logger\Colissimo;
use LaPoste\Colissimo\Helper\Bordereau;

/**
 * Class GenerateBordereau to generate bordereau from selected labels via scan
 * @package LaPoste\Colissimo\Controller\Adminhtml\Bordereau
 */
class GenerateBordereau extends Action
{
    /**
     * @var Colissimo
     */
    protected $logger;

    /**
     * @var Bordereau
     */
    protected $bordereauHelper;

    /**
     * GenerateBordereau constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \LaPoste\Colissimo\Logger\Colissimo $logger
     * @param \LaPoste\Colissimo\Helper\Bordereau $bordereauHelper
     */
    public function __construct(
        Context $context,
        Colissimo $logger,
        Bordereau $bordereauHelper
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->bordereauHelper = $bordereauHelper;
    }

    /**
     * Generate bordereau and redirect on listing
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {

        $inputTracks = $this->getRequest()->getParam('lpc_scan_barcodes');
        $inputTracks = str_replace(["\r\n", "\n", "\r"], ',', $inputTracks);
        $parcelNumbers = explode(',', trim($inputTracks, ','));
        $resultRedirectFactory = $this->resultRedirectFactory->create();

        try {
            $trackingNumbers = array_unique($parcelNumbers);

            if ($this->bordereauHelper->generateBordereau($trackingNumbers)) {
                $this->messageManager->addSuccessMessage(__('Delivery docket has been generated.'));

                return $resultRedirectFactory->setPath('laposte_colissimo/bordereau');
            } else {
                $this->messageManager->addErrorMessage(__('Unable to generate delivery docket.'));

                return $resultRedirectFactory->setPath('laposte_colissimo/bordereau/generateviascan');
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__, ['exception' => $e->getMessage()]);
            $this->messageManager->addErrorMessage(__('Unable to generate delivery docket (see logs for details).'));

            return $resultRedirectFactory->setPath('laposte_colissimo/bordereau/generateviascan');
        }
    }

}
