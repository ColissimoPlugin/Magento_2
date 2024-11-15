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


use LaPoste\Colissimo\Block\Adminhtml\Shipment\Label\AbstractLabel;
use LaPoste\Colissimo\Cron\PurgeLabelFolder;
use LaPoste\Colissimo\Helper\Shipment;
use LaPoste\Colissimo\Model\Carrier\Colissimo as ColissimoCarrier;
use LaPoste\Colissimo\Model\Shipping\ReturnLabelGenerator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order\Pdf\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
use LaPoste\Colissimo\Logger\Colissimo;
use LaPoste\Colissimo\Helper\Pdf;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\MassAction\Filter;

class MassPrintLabels extends Action
{
    const ADMIN_RESOURCE = 'LaPoste_Colissimo::shipment';
    /**
     * @var CollectionFactory
     */
    protected $shipmentCollection;
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var ReturnLabelGenerator
     */
    protected $labelGenerator;
    /**
     * @var Shipment
     */
    protected $shipmentHelper;
    /**
     * @var Pdf
     */
    protected $pdfHelper;
    /**
     * @var PageFactory
     */
    protected $pageFactory;
    /**
     * @var WriteInterface
     */
    protected $tmpDirectory;
    /**
     * @var Colissimo
     */
    protected $logger;
    /**
     * @var Filesystem
     */
    protected $filesystem;
    /**
     * @var Invoice
     */
    protected $pdfInvoice;
    /**
     * @var \Magento\Sales\Model\Order\Shipment
     */
    protected $shipment;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    protected $pdfFile;

    protected $pages;

    protected $filter;


    /**
     * MassPrintLabels constructor.
     *
     * @param Context               $context
     * @param CollectionFactory     $shipmentCollection
     * @param ReturnLabelGenerator  $labelGenerator
     * @param Shipment              $shipmentHelper
     * @param Pdf                   $pdfHelper
     * @param PageFactory           $pageFactory
     * @param Filesystem            $filesystem
     * @param Colissimo             $logger
     * @param Invoice               $pdfInvoice
     * @param StoreManagerInterface $storeManager
     * @param Filter                $filter
     */
    public function __construct(
        Context $context,
        CollectionFactory $shipmentCollection,
        ReturnLabelGenerator $labelGenerator,
        Shipment $shipmentHelper,
        Pdf $pdfHelper,
        PageFactory $pageFactory,
        Filesystem $filesystem,
        Colissimo $logger,
        Invoice $pdfInvoice,
        StoreManagerInterface $storeManager,
        Filter $filter
    ) {
        parent::__construct($context);

        $this->shipmentCollection = $shipmentCollection;
        $this->request = $context->getRequest();
        $this->labelGenerator = $labelGenerator;
        $this->shipmentHelper = $shipmentHelper;
        $this->pdfHelper = $pdfHelper;
        $this->pageFactory = $pageFactory;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->pdfInvoice = $pdfInvoice;
        $this->storeManager = $storeManager;
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
            $shipments = $this->filter->getCollection($this->shipmentCollection->create());

            // Magento 2.1 returns null for param selected if all selected
            // In this case we will get all shipments and filter non Colissimo ones later
            if (!empty($this->getRequest()->getParam('selected'))) {
                $shipments = $shipments->addFieldToFilter('entity_id', $this->getRequest()->getParam('selected'));
            }

            $this->pages = [];
            foreach ($shipments as $shipment) {
                $shippingMethod = $shipment->getOrder()->getShippingMethod();
                if (false === strpos($shippingMethod, ColissimoCarrier::CODE . '_')) {
                    continue; // Remove non colissimo shipments
                }

                $this->shipment = $shipment;

                $this->getPdf();
            }

            if (empty($this->pages)) {
                return $this->onError(__('No labels found.'));
            }

            $this->pdfFile = $this->pdfHelper->combineLabelsPdf($this->pages);

            $filePath = $this->storeManager->getStore()
                                           ->getBaseUrl(UrlInterface::URL_TYPE_WEB) . $this->savePdfFile();

            $resultPage = $this->pageFactory->create();

            /** @var \Magento\Framework\View\Layout $layout */
            $layout = $resultPage->getLayout();
            $layout->getUpdate()->removeHandle('default');

            if (!$layout->hasElement('lpc.mass.label.pdf.wrapper')) {
                throw new \Exception(__('Layout is not appropriate'));
            }

            $printBlock = $layout->addBlock(
                $this->getCorrespondingLabelBlock(),
                'lpc.label',
                'lpc.mass.label.pdf.wrapper'
            );

            $printBlock->setDataUsingMethod('pdf_path', $filePath);

            return $resultPage;
        } catch (\Exception $e) {
            return $this->onError($e->getMessage());
        }
    }


    /**
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Exception
     */
    public function savePdfFile()
    {
        $pdfFile = $this->pdfFile;
        $filePath = PurgeLabelFolder::FOLDER_PATH .
                    DIRECTORY_SEPARATOR .
                    'lpc_orders_' .
                    date('Y-m-d_H-i-s') .
                    '.pdf';

        $this->tmpDirectory->create(PurgeLabelFolder::FOLDER_PATH);
        if (!$this->tmpDirectory->isExist($filePath)) {
            $this->tmpDirectory->writeFile($filePath, $pdfFile->render());
        }

        return $this->tmpDirectory->getRelativePath($filePath);
    }

    /**
     * @param $message
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function onError($message)
    {
        $message = __('An error occurred during printing') . ': ' . $message;

        $this->logger->error($message);
        $this->messageManager->addErrorMessage($message);

        return $this->resultRedirectFactory->create()->setPath('laposte_colissimo/shipment/index');
    }

    /**
     * @return bool
     * @throws \Zend_Pdf_Exception
     */
    public function getPdf()
    {
        $pdfData = $this->shipment->getDataUsingMethod('shipping_label');
        if (!empty($pdfData)) {
            $this->pages[] = $pdfData;
        }

        $pdfData = $this->shipment->getDataUsingMethod('lpc_return_label');
        if (!empty($pdfData)) {
            $this->pages[] = $pdfData;
        }

        $this->pages[] = $this->getInvoicePdfContent();

        // We want 2 invoices when there's a CN23
        if ($this->shipment->getDataUsingMethod('lpc_label_cn_23')) {
            $this->pages[] = $this->getInvoicePdfContent();
        }

        return true;
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

    /**
     * @return string
     * @throws \Exception
     */
    public function getCorrespondingLabelBlock()
    {
        return AbstractLabel::class;
    }

}
