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

use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Helper\Pdf;
use LaPoste\Colissimo\Helper\Shipment;
use LaPoste\Colissimo\Logger\Colissimo;
use LaPoste\Colissimo\Model\AccountApi;
use LaPoste\Colissimo\Model\Shipping\ReturnLabelGenerator;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Magento\Framework\Controller\Result\JsonFactory;

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
    protected $helperData;
    protected $accountApi;
    protected $orderRepository;
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * PrintReturnLabel constructor.
     * @param Context                  $context
     * @param LabelGenerator           $labelGenerator
     * @param FileFactory              $fileFactory
     * @param Colissimo                $logger
     * @param ShipmentRepository       $shipmentRepository
     * @param ReturnLabelGenerator     $returnLabelGenerator
     * @param Shipment                 $shipmentHelper
     * @param Pdf                      $helperPdf
     * @param Data                     $helperData
     * @param AccountApi               $accountApi
     * @param OrderRepositoryInterface $orderRepository
     * @param JsonFactory              $resultJsonFactory
     */
    public function __construct(
        Context $context,
        LabelGenerator $labelGenerator,
        FileFactory $fileFactory,
        Colissimo $logger,
        ShipmentRepository $shipmentRepository,
        ReturnLabelGenerator $returnLabelGenerator,
        \LaPoste\Colissimo\Helper\Shipment $shipmentHelper,
        Pdf $helperPdf,
        Data $helperData,
        AccountApi $accountApi,
        OrderRepositoryInterface $orderRepository,
        JsonFactory $resultJsonFactory
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
        $this->helperData = $helperData;
        $this->accountApi = $accountApi;
        $this->orderRepository = $orderRepository;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Print label for one specific shipment
     *
     * @return ResponseInterface|void
     */
    public function execute()
    {
        $shipmentId = $this->getRequest()->getParam('shipmentId');
        if (!empty($shipmentId)) {
            $shipment = $this->shipmentRepository->get($shipmentId);

            return $this->downloadLabel($shipment);
        }

        $resultJson = $this->resultJsonFactory->create();

        $orderId = $this->getRequest()->getParam('orderId');
        $order = $this->orderRepository->get($orderId);

        // Get products to return
        $productQtyIds = json_decode($this->getRequest()->getParam('productIds'));
        $productToReturn = [];
        foreach ($productQtyIds as $oneProductQtyId) {
            $oneProductWithQty = explode('_', $oneProductQtyId);
            $productToReturn[$oneProductWithQty[0]] = $oneProductWithQty[1];
        }

        // Get option secured return
        $accountInformation = $this->accountApi->getAccountInformation();
        $isSecuredReturnEnabled = '1' === $this->helperData->getAdvancedConfigValue('lpc_return_labels/securedReturn');

        $isSecuredReturn = false;
        if (!empty($accountInformation['optionRetourToken']) && $isSecuredReturnEnabled) {
            // Should generate token to return in La Poste office
            $isSecuredReturn = true;
        }

        // Need one shipment from the order to generate a label
        $shipments = $order->getShipmentsCollection();
        foreach ($shipments as $oneShipment) {
            unset($oneShipment['shipping_label']);
            $shipment = $oneShipment;
        }
        if (empty($shipment)) {
            return $resultJson->setData(
                [
                    'success' => false,
                    'error'   => __('Shipment not found'),
                ]
            );
        }

        try {
            $packages = $this->shipmentHelper->partialShipmentToPackages($order, $productToReturn);
            $this->request->setParams(['packages' => $packages]);

            $trackingNumbers = $this->returnLabelGenerator->createReturnLabel($shipment, $this->request, $isSecuredReturn);

            return $resultJson->setData(
                [
                    'success'        => true,
                    'trackingNumber' => array_pop($trackingNumbers),
                    'shipmentId'     => $shipment->getId(),
                ]
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

        return $resultJson->setData(
            [
                'success' => false,
                'error'   => __('An error occurred while creating shipping label.'),
            ]
        );
    }

    private function downloadLabel($shipment)
    {
        $labelContent = $shipment->getLpcReturnLabel();

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
    }
}
