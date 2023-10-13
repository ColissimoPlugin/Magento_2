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


use LaPoste\Colissimo\Block\Adminhtml\Shipment\Label\InwardLabel;
use LaPoste\Colissimo\Block\Adminhtml\Shipment\Label\OutwardLabel;
use LaPoste\Colissimo\Cron\PurgeLabelFolder;
use LaPoste\Colissimo\Logger\Colissimo;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Event\Manager;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class PrintLabel extends Action implements PrintLabelInterface
{
    const LABEL_TYPE_INWARD = 'inward';
    const LABEL_TYPE_OUTWARD = 'outward';
    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;
    /**
     * @var \LaPoste\Colissimo\Logger\Colissimo
     */
    protected $logger;
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $redirectFactory;
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Magento\Sales\Model\Order\Shipment
     */
    protected $shipment;

    /**
     * @var string
     */
    protected $labelType;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $tmpDirectory;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;


    /**
     * PrintLabel constructor.
     *
     * @param \Magento\Backend\App\Action\Context                  $context
     * @param \LaPoste\Colissimo\Logger\Colissimo                  $logger
     * @param \Magento\Framework\Event\Manager                     $eventManager
     * @param \Magento\Framework\Filesystem                        $filesystem
     * @param \Magento\Framework\View\Result\PageFactory           $pageFactory
     * @param \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManager
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface       $shipmentRepository
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        Colissimo $logger,
        Manager $eventManager,
        Filesystem $filesystem,
        PageFactory $pageFactory,
        RedirectFactory $redirectFactory,
        StoreManagerInterface $storeManager,
        ShipmentRepositoryInterface $shipmentRepository
    ) {
        parent::__construct($context);

        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->pageFactory = $pageFactory;
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
        $this->messageManager = $context->getMessageManager();
        $this->redirectFactory = $redirectFactory;
        $this->shipmentRepository = $shipmentRepository;

        $this->tmpDirectory = $filesystem->getDirectoryWrite(DirectoryList::PUB);
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     */
    public function execute()
    {
        try {
            $request = $this->getRequest();
            $shipmentId = $request->getParam('shipment_id');
            $this->labelType = $request->getParam('label_type');

            if (null === $shipmentId ||
                null === $this->labelType) {
                throw new \Exception(__('Parameters are incorrect.'));
            }

            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            $this->shipment = $this->shipmentRepository->get($shipmentId);

            $filePath = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB)
                        . $this->createPdfFile();


            $resultPage = $this->pageFactory->create();
            /** @var \Magento\Framework\View\Layout $layout */
            $layout = $resultPage->getLayout();
            $layout->getUpdate()->removeHandle('default');

            if (!$layout->hasElement('lpc.label.pdf.wrapper')) {
                throw new \Exception(__('Layout is not appropriate'));
            }

            $printBlock = $layout->addBlock(
                $this->getCorrespondingLabelBlock(),
                'lpc.label',
                'lpc.label.pdf.wrapper'
            );

            $printBlock->setDataUsingMethod('pdf_path', $filePath);

            return $resultPage;
        } catch (\Exception $e) {
            return $this->onError($e->getMessage());
        }
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
     * @return string
     * @throws \Exception
     */
    public function getCorrespondingLabelBlock()
    {
        switch ($this->labelType) {
            case self::LABEL_TYPE_INWARD:
                $blockClass = InwardLabel::class;
                break;
            case self::LABEL_TYPE_OUTWARD:
                $blockClass = OutwardLabel::class;
                break;
            default:
                throw new \Exception(__('Parameters are incorrect.'));
        }

        return $blockClass;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Exception
     */
    public function createPdfFile()
    {
        $pdfContent = $this->getPdfContent();
        $filePath = PurgeLabelFolder::FOLDER_PATH .
                    DIRECTORY_SEPARATOR .
                    hash('md5', $pdfContent) .
                    '.pdf';

        $this->tmpDirectory->create(PurgeLabelFolder::FOLDER_PATH);
        if (!$this->tmpDirectory->isExist($filePath)) {
            $this->tmpDirectory->writeFile($filePath, $pdfContent);
        }

        return $this->tmpDirectory->getRelativePath($filePath);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getPdfContent()
    {
        switch ($this->labelType) {
            case self::LABEL_TYPE_INWARD:
                $attribute = 'lpc_return_label';
                break;
            case self::LABEL_TYPE_OUTWARD:
                $attribute = 'shipping_label';
                break;
            default:
                throw new \Exception(__('Parameters are incorrect.'));
        }

        $pdfContent = $this->shipment->getDataUsingMethod($attribute);
        if (null === $pdfContent) {
            throw new \Exception(__('Cannot print label before generation.'));
        }

        return $pdfContent;
    }

    /**
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function getShipment()
    {
        return $this->shipment;
    }


}
