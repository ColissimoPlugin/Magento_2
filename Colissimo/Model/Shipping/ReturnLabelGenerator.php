<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Model\Shipping;

use LaPoste\Colissimo\Helper\Pdf;
use Magento\Framework\App\RequestInterface;

// can't simply extends \Magento\Shipping\Model\Shipping\LabelGenerator because it's constructor
// needs a \Magento\Shipping\Model\Shipping\LabelsFactory and not just an interface to it.
class ReturnLabelGenerator
{
    /**
     * @var \Magento\Shipping\Model\CarrierFactory
     */
    protected $carrierFactory;

    /**
     * @var \Magento\Shipping\Model\Shipping\LabelsFactory
     */
    protected $labelFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $trackFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;
    /**
     * @var \LaPoste\Colissimo\Helper\Pdf
     */
    protected $helperPdf;


    /**
     * ReturnLabelGenerator constructor.
     * @param \Magento\Shipping\Model\CarrierFactory                $carrierFactory
     * @param \LaPoste\Colissimo\Model\Shipping\ReturnLabelsFactory $labelFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface    $scopeConfig
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory      $trackFactory
     * @param \Magento\Framework\Filesystem                         $filesystem
     * @param \LaPoste\Colissimo\Helper\Pdf                         $helperPdf
     */
    public function __construct(
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \LaPoste\Colissimo\Model\Shipping\ReturnLabelsFactory $labelFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Magento\Framework\Filesystem $filesystem,
        Pdf $helperPdf
    ) {
        $this->carrierFactory = $carrierFactory;
        $this->labelFactory = $labelFactory;
        $this->scopeConfig = $scopeConfig;
        $this->trackFactory = $trackFactory;
        $this->filesystem = $filesystem;
        $this->helperPdf = $helperPdf;
    }

    public function createReturnLabel(
        \Magento\Sales\Model\Order\Shipment $shipment,
        RequestInterface $request,
        bool $isSecuredReturn = false
    ) {
        $order = $shipment->getOrder();
        $carrier = $this->carrierFactory->create($order->getShippingMethod(true)->getCarrierCode());
        if (!$carrier->isShippingLabelsAvailable()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Shipping label is not available.'));
        }
        $shipment->setPackages($request->getParam('packages'));
        $response = $this->labelFactory->create()->returnOfShipment($shipment, $isSecuredReturn); // That's the only line being overriden

        if ($response->hasErrors()) {
            throw new \Magento\Framework\Exception\LocalizedException(__($response->getErrors()));
        }
        if (!$response->hasInfo()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Response info is not exist.'));
        }
        $labelsContent = [];
        $trackingNumbers = [];
        $info = $response->getInfo();
        foreach ($info as $inf) {
            if (!empty($inf['tracking_number']) && !empty($inf['label_content'])) {
                $labelsContent[] = $inf['label_content'];
                $trackingNumbers[] = $inf['tracking_number'];
            }
        }
        $outputPdf = $this->helperPdf->combineLabelsPdf($labelsContent);
        $shipment->setLpcReturnLabel($outputPdf->render());
        $carrierCode = $carrier->getCarrierCode();
        $carrierTitle = $this->scopeConfig->getValue(
            'carriers/' . $carrierCode . '/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $shipment->getStoreId()
        );
        if (!empty($trackingNumbers)) {
            $this->addTrackingNumbersToShipment($shipment, $trackingNumbers, $carrierCode, $carrierTitle);
        }

        return $trackingNumbers;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param array                               $trackingNumbers
     * @param string                              $carrierCode
     * @param string                              $carrierTitle
     *
     * @return void
     */
    private function addTrackingNumbersToShipment(
        \Magento\Sales\Model\Order\Shipment $shipment,
        $trackingNumbers,
        $carrierCode,
        $carrierTitle
    ) {
        foreach ($trackingNumbers as $number) {
            if (is_array($number)) {
                $this->addTrackingNumbersToShipment($shipment, $number, $carrierCode, $carrierTitle);
            } else {
                $shipment->addTrack(
                    $this->trackFactory->create()
                                       ->setNumber($number)
                                       ->setCarrierCode($carrierCode)
                                       ->setTitle($carrierTitle)
                );
            }
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param                                     $trackingNumbers
     * @param                                     $carrierCode
     * @param                                     $carrierTitle
     */
    public function addTrackNumbers(
        \Magento\Sales\Model\Order\Shipment $shipment,
        $trackingNumbers,
        $carrierCode,
        $carrierTitle
    ) {
        $this->addTrackingNumbersToShipment($shipment, $trackingNumbers, $carrierCode, $carrierTitle);
    }
}
