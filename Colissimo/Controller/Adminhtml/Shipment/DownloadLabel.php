<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Controller\Adminhtml\Shipment;

use LaPoste\Colissimo\Helper\Pdf;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Sales\Model\Order\Pdf\Invoice;

class DownloadLabel extends Action
{
    const LABEL_TYPE_INWARD = 'inward';
    const LABEL_TYPE_OUTWARD = 'outward';

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::shipment';

    /**
     * @var \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader
     */
    protected $shipmentLoader;

    /**
     * @var \Magento\Shipping\Model\Shipping\LabelGenerator
     */
    protected $labelGenerator;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * @var \LaPoste\Colissimo\Logger\Colissimo
     */
    protected $logger;
    /**
     * @var \LaPoste\Colissimo\Helper\Pdf
     */
    protected $helperPdf;
    /**
     * @var \Magento\Sales\Model\Order\Pdf\Invoice
     */
    private $pdfInvoice;

    /**
     * @param Action\Context $context
     * @param \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader
     * @param \Magento\Shipping\Model\Shipping\LabelGenerator $labelGenerator
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \LaPoste\Colissimo\Logger\Colissimo $logger
     * @param \LaPoste\Colissimo\Helper\Pdf $helperPdf
     * @param \Magento\Sales\Model\Order\Pdf\Invoice $pdfInvoice
     */
    public function __construct(
        Action\Context $context,
        \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader,
        \Magento\Shipping\Model\Shipping\LabelGenerator $labelGenerator,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \LaPoste\Colissimo\Logger\Colissimo $logger,
        Pdf $helperPdf,
        Invoice $pdfInvoice

    ) {
        parent::__construct($context);

        $this->shipmentLoader = $shipmentLoader;
        $this->labelGenerator = $labelGenerator;
        $this->_fileFactory = $fileFactory;
        $this->logger = $logger;
        $this->helperPdf = $helperPdf;
        $this->pdfInvoice = $pdfInvoice;
    }

    /**
     * Print label for one specific shipment
     *
     * @return ResponseInterface|void
     */
    public function execute()
    {
        try {
            $this->shipmentLoader->setOrderId($this->getRequest()->getParam('order_id'));
            $this->shipmentLoader->setShipmentId($this->getRequest()->getParam('shipment_id'));
            $this->shipmentLoader->setShipment($this->getRequest()->getParam('shipment'));
            $this->shipmentLoader->setTracking($this->getRequest()->getParam('tracking'));
            $labelType = $this->getRequest()->getParam('label_type');
            $shipment = $this->shipmentLoader->load();

            if ($labelType == self::LABEL_TYPE_OUTWARD) {
                $labelContent = $shipment->getShippingLabel();
                $fileName = 'ShippingLabel';
                $noLabelMsg = __('Please generate label');
            } else {
                $labelContent = $shipment->getLpcReturnLabel();
                $fileName = 'ReturnShippingLabel';
                $noLabelMsg = __('Please generate inward label');
            }

            if ($labelContent) {
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

                // Add invoices
                $pdfContent = $this->addInvoiceToLabel($shipment, $pdfContent);

                return $this->_fileFactory->create(
                    $fileName . '(' . $shipment->getIncrementId() . ').pdf',
                    $pdfContent,
                    DirectoryList::VAR_DIR,
                    'application/pdf'
                );
            } else {
                $this->messageManager->addNoticeMessage($noLabelMsg);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            $this->logger->error(
                __METHOD__,
                [
                    'exception' => $e,
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__,
                [
                    'exception' => $e,
                ]
            );

            $this->messageManager->addErrorMessage(__('An error occurred while creating shipping label.'));
        }

        return $this->_redirect('laposte_colissimo/shipment/index');
    }

    /**
     * @param $shipment
     * @param $pdfContent
     * @return string
     * @throws \Zend_Pdf_Exception
     */
    protected function addInvoiceToLabel($shipment, $pdfContent)
    {
        $invoicePdfContent = $this->getInvoicePdf($shipment)->render();
        $pdfLabels = [
            $pdfContent,
            $invoicePdfContent,
        ];

        if ($shipment->getDataUsingMethod('lpc_label_cn_23')) {
            $pdfLabels[] = $invoicePdfContent;
        }

        try {
            $newPdfContent = $this->helperPdf->combineLabelsPdf($pdfLabels)->render();
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
            $newPdfContent = $pdfContent;
        }

        return $newPdfContent;
    }

    /**
     * @param $shipment
     * @return \Zend_Pdf
     */
    protected function getInvoicePdf($shipment)
    {
        $invoiceCollection = $shipment->getOrder()->getInvoiceCollection();

        return $this->pdfInvoice->getPdf($invoiceCollection);
    }
}
