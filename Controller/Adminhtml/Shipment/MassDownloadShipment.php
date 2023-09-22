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

use LaPoste\Colissimo\Cron\PurgeLabelFolder;
use LaPoste\Colissimo\Helper\Pdf;
use LaPoste\Colissimo\Logger\Colissimo;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Sales\Model\Order\Pdf\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use LaPoste\Colissimo\Model\Carrier\Colissimo as ColissimoCarrier;
use Magento\Ui\Component\MassAction\Filter;

class MassDownloadShipment extends Action
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $shipmentCollection;
    /**
     * @var \LaPoste\Colissimo\Helper\Pdf
     */
    protected $helperPdf;

    /**
     * @var \Magento\Sales\Model\Order\Shipment
     */
    protected $shipment;
    /**
     * @var \Magento\Sales\Model\Order\Pdf\Invoice
     */
    protected $pdfInvoice;
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $redirectFactory;
    /**
     * @var \LaPoste\Colissimo\Logger\Colissimo
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $tmpDirectory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;
    protected $filter;

    /**
     * massDownloadShipment constructor.
     *
     * @param \LaPoste\Colissimo\Helper\Pdf                                       $helperPdf
     * @param \Magento\Backend\App\Action\Context                                 $context
     * @param \LaPoste\Colissimo\Logger\Colissimo                                 $logger
     * @param \Magento\Sales\Model\Order\Pdf\Invoice                              $pdfInvoice
     * @param \Magento\Framework\App\Response\Http\FileFactory                    $fileFactory
     * @param \Magento\Framework\Controller\Result\RedirectFactory                $redirectFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollection
     * @param \Magento\Framework\Filesystem                                       $filesystem
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Pdf $helperPdf,
        Context $context,
        Colissimo $logger,
        Invoice $pdfInvoice,
        FileFactory $fileFactory,
        RedirectFactory $redirectFactory,
        CollectionFactory $shipmentCollection,
        Filesystem $filesystem,
        Filter $filter
    ) {
        parent::__construct($context);

        $this->logger = $logger;
        $this->helperPdf = $helperPdf;
        $this->pdfInvoice = $pdfInvoice;
        $this->fileFactory = $fileFactory;
        $this->messageManager = $context->getMessageManager();
        $this->redirectFactory = $redirectFactory;
        $this->shipmentCollection = $shipmentCollection;
        $this->filesystem = $filesystem;
        $this->filter = $filter;

        $this->tmpDirectory = $filesystem->getDirectoryWrite(DirectoryList::PUB);
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        try {
            $pdfs = [];

            $shipments = $this->filter->getCollection($this->shipmentCollection->create());

            // Magento 2.1 returns null for param selected if all selected
            // In this case we will get all shipments and filter non Colissimo ones later
            if (!empty($this->getRequest()->getParam('selected'))) {
                $shipments = $shipments->addFieldToFilter('entity_id', $this->getRequest()->getParam('selected'));
            }

            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            foreach ($shipments as $shipment) {
                $shippingMethod = $shipment->getOrder()->getShippingMethod();
                if (false === strpos($shippingMethod, ColissimoCarrier::CODE . '_')) {
                    continue; // Remove non colissimo shipments
                }
                $this->shipment = $shipment;

                $pdfs[] = [
                    'label'   => 'Order_' . $shipment->getOrderId() . '_shipment_' . $shipment->getIncrementId() . '.pdf',
                    'content' => $this->getPdf()->render(),
                ];
            }

            $zip = new \ZipArchive();
            $timeStamp = date('Y-m-d_H-i');
            $fileName = 'lpc_orders_' . $timeStamp;

            $this->tmpDirectory->create(PurgeLabelFolder::FOLDER_PATH);

            if ($zip->open(
                    PurgeLabelFolder::FOLDER_PATH . DIRECTORY_SEPARATOR . $fileName,
                    \ZipArchive::CREATE
                ) === true) {
                foreach ($pdfs as $pdf) {
                    $zip->addFromString($pdf['label'], $pdf['content']);
                }
                $filePath = $zip->filename;
                $zip->close();
            } else {
                throw new \Exception(__('Unable to create zip file'));
            }

            return $this->fileFactory->create(
                'lpc_orders_' . $timeStamp . '.zip',
                [
                    'type'  => 'filename',
                    'value' => $filePath,
                    'rm'    => true,
                ]
            );
        } catch (\Exception $e) {
            return $this->onError($e->getMessage());
        }
    }

    public function onError($message)
    {
        $message = __('An error occurred during download') . ': ' . $message;
        $this->logger->error($message);
        $this->messageManager->addErrorMessage($message);

        return $this->redirectFactory->create()->setPath('laposte_colissimo/shipment/index');
    }

    /**
     * @return \Zend_Pdf
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Pdf_Exception
     */
    public function getPdf()
    {
        $invoicePdf = $this->getInvoicePdfContent();
        $pdfLabels = [
            $this->getInwardLabelPdfContent(),
            $this->getOutwardLabelPdfContent(),
            $invoicePdf,
        ];

        if ($this->shipment->getDataUsingMethod('lpc_label_cn_23')) {
            $pdfLabels[] = $invoicePdf;
        }

        return $this->helperPdf->combineLabelsPdf($pdfLabels);
    }

    /**
     * @return mixed
     */
    public function getInwardLabelPdfContent()
    {
        return $this->shipment->getDataUsingMethod('lpc_return_label');
    }

    /**
     * @return mixed
     */
    public function getOutwardLabelPdfContent()
    {
        return $this->shipment->getDataUsingMethod('shipping_label');
    }

    /**
     * @return string
     * @throws \Zend_Pdf_Exception
     */
    public function getInvoicePdfContent()
    {
        $invoiceCollection = $this->shipment->getOrder()->getInvoiceCollection();

        return $this->pdfInvoice->getPdf($invoiceCollection)->render();
    }
}
