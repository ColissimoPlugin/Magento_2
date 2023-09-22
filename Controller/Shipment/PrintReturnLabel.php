<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Controller\Shipment;

use LaPoste\Colissimo\Helper\Pdf;
use LaPoste\Colissimo\Logger\Colissimo;
use LaPoste\Colissimo\Model\Shipping\ReturnLabelGenerator;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Shipping\Model\Shipping\LabelGenerator;

class PrintReturnLabel extends \Magento\Framework\App\Action\Action
{
    /**
     * @var LabelGenerator
     */
    protected $labelGenerator;
    /**
     * @var FileFactory
     */
    protected $fileFactory;
    /**
     * @var Colissimo
     */
    protected $logger;
    /**
     * @var ShipmentRepository
     */
    protected $shipmentRepository;
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var ReturnLabelGenerator
     */
    protected $returnLabelGenerator;

    protected $shipmentHelper;
    /**
     * @var \LaPoste\Colissimo\Helper\Pdf
     */
    protected $helperPdf;

    /**
     * PrintReturnLabel constructor.
     * @param Context                            $context
     * @param LabelGenerator                     $labelGenerator
     * @param FileFactory                        $fileFactory
     * @param Colissimo                          $logger
     * @param ShipmentRepository                 $shipmentRepository
     * @param ReturnLabelGenerator               $returnLabelGenerator
     * @param \LaPoste\Colissimo\Helper\Shipment $shipmentHelper
     * @param \LaPoste\Colissimo\Helper\Pdf      $helperPdf
     */
    public function __construct(
        Context $context,
        LabelGenerator $labelGenerator,
        FileFactory $fileFactory,
        Colissimo $logger,
        ShipmentRepository $shipmentRepository,
        ReturnLabelGenerator $returnLabelGenerator,
        \LaPoste\Colissimo\Helper\Shipment $shipmentHelper,
        Pdf $helperPdf
    ) {
        parent::__construct($context);
        $this->labelGenerator = $labelGenerator;
        $this->fileFactory = $fileFactory;
        $this->logger = $logger;
        $this->shipmentRepository = $shipmentRepository;
        $this->request = $context->getRequest();
        $this->returnLabelGenerator = $returnLabelGenerator;
        $this->shipmentHelper = $shipmentHelper;
        $this->helperPdf = $helperPdf;
    }

    /**
     * Print label for one specific shipment
     *
     * @return ResponseInterface|void
     */
    public function execute()
    {
        try {
            $shipment = $this->shipmentRepository->get(
                $this->getRequest()->getParam('shipment_id')
            );

            $labelContent = $shipment->getLpcReturnLabel();

            if (null === $labelContent) {
                $packages = $this->shipmentHelper
                    ->shipmentToPackages($shipment);

                $this->request->setParams([
                                              'packages' => $packages,
                                          ]);

                $this->returnLabelGenerator->createReturnLabel($shipment, $this->request);

                $labelContent = $shipment->getLpcReturnLabel();
            }

            $pdfContent = null;
            if (stripos($labelContent, '%PDF-') !== false) {
                $pdfContent = $labelContent;
            } else {
                $pdf = new \Zend_Pdf();
                $page = $this->helperPdf->createPdfPageFromImageString($labelContent);
                if (!$page) {
                    $this->messageManager->addErrorMessage(
                        __(
                            'We don\'t recognize or support the file extension in this shipment: %1.',
                            $shipment->getIncrementId()
                        )
                    );
                }
                $pdf->pages[] = $page;
                $pdfContent = $pdf->render();
            }

            return $this->fileFactory->create(
                'ReturnShippingLabel(' . $shipment->getIncrementId() . ').pdf',
                $pdfContent,
                DirectoryList::VAR_DIR,
                'application/pdf'
            );
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__,
                [
                    'exception' => $e,
                ]
            );

            $this->messageManager->addErrorMessage(__('An error occurred while creating shipping label.'));
        }

        return $this->_redirect('sales/order/history');
    }
}
