<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Observer;

use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Helper\Shipment;
use LaPoste\Colissimo\Logger\Colissimo;
use Magento\Framework\App\RequestInterface;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Magento\Backend\Model\Auth\Session;

class GenerateLabelOnOrderStateChange implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \LaPoste\Colissimo\Helper\Data
     */
    protected $helperData;
    /**
     * @var \LaPoste\Colissimo\Logger\Colissimo
     */
    protected $logger;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    /**
     * @var \Magento\Shipping\Model\Shipping\LabelGenerator
     */
    protected $labelGenerator;
    /**
     * @var \LaPoste\Colissimo\Helper\Shipment
     */
    protected $shipmentHelper;

    protected $authSession;

    /**
     * GenerateLabelOnOrderStateChange constructor.
     *
     * @param \LaPoste\Colissimo\Helper\Data                  $helperData
     * @param \LaPoste\Colissimo\Logger\Colissimo             $logger
     * @param \Magento\Framework\App\RequestInterface         $request
     * @param \Magento\Shipping\Model\Shipping\LabelGenerator $labelGenerator
     * @param \LaPoste\Colissimo\Helper\Shipment              $shipmentHelper
     */
    public function __construct(
        Data $helperData,
        Colissimo $logger,
        RequestInterface $request,
        LabelGenerator $labelGenerator,
        Shipment $shipmentHelper,
        Session $authSession
    ) {
        $this->helperData = $helperData;
        $this->logger = $logger;
        $this->request = $request;
        $this->labelGenerator = $labelGenerator;
        $this->shipmentHelper = $shipmentHelper;
        $this->authSession = $authSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            if ($this->helperData->isUsingColiShip()) {
                return $this;
            }

            $order = $observer->getEvent()->getOrder();

            $orderStatusOption = $this->helperData->getAdvancedConfigValue(
                'lpc_labels/orderStatusForGeneration',
                $order->getStoreId()
            );
            if(empty($orderStatusOption)) {
                return $this;
            }

            $orderStatusesForGeneration = explode(',', $orderStatusOption);

            $orderStatusesForGeneration = array_filter($orderStatusesForGeneration, function ($v) {
                return !empty($v);
            });

            if (
                empty($orderStatusesForGeneration)
                || !($order instanceof \Magento\Framework\Model\AbstractModel)
                || $order->getIsVirtual()
                || (\LaPoste\Colissimo\Model\Carrier\Colissimo::CODE !== $order->getShippingMethod(true)->getCarrierCode())
                || !in_array($order->getStatus(), $orderStatusesForGeneration)
            ) {
                return $this;
            }

            // the label should automatically be generated
            $this->logger->info(
                'Automatically generating label',
                [
                    'order_id'           => $order->getId(),
                    'order_increment_id' => $order->getIncrementId(),
                    'status'             => $order->getStatus(),
                ]
            );

            $currentUser = $this->authSession->getUser();
            if (empty($currentUser)) {
                $this->logger->warning(
                    'Label not automatically generated because auth session user',
                    [
                        'order_id'           => $order->getId(),
                        'order_increment_id' => $order->getIncrementId(),
                    ]
                );

                return $this;
            }

            if ($order->canShip()) {
                // generate the whole shipment
                $shipment = $this->shipmentHelper->createShipment($order);

                // trigger a label generation for this shipment
                $this->generateLabel($shipment);

                $this->logger->info(
                    'Label automatically generated',
                    [
                        'order_id'           => $order->getId(),
                        'order_increment_id' => $order->getIncrementId(),
                    ]
                );
            } else {
                $this->logger->warning(
                    'Label not automatically generated because not order canShip',
                    [
                        'order_id'           => $order->getId(),
                        'order_increment_id' => $order->getIncrementId(),
                    ]
                );
            }
        } catch (\LocalizedException $e) {
            $this->logger->error('An error occurred!', ['e' => $e]);
        } catch (\Exception $e) {
            $this->logger->error('An error occurred!', ['e' => $e]);
        }

        return $this;
    }


    protected function generateLabel(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $packages = $this->shipmentHelper
            ->shipmentToPackages($shipment);


        $this->request
            ->setParam('packages', $packages);

        try {
            $this->labelGenerator->create($shipment, $this->request);
        } catch (\LocalizedException $e) {
            $this->logger->error('An error occured while generating label!', ['e' => $e]);
        }

        $shipment->save();
    }
}
