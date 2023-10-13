<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Model;

use LaPoste\Colissimo\Logger;
use LaPoste\Colissimo\Helper;
use LaPoste\Colissimo\Exception;
use LaPoste\Colissimo\Api\ColissimoStatus;

class TrackingApi implements \LaPoste\Colissimo\Api\TrackingApi
{
    const API_BASE_URL = 'https://www.coliposte.fr/tracking-chargeur-cxf/TrackingServiceWS/track';
    const UPDATE_STATUS_PERIOD = '-15 days';

    protected $logger;

    protected $helperData;

    protected $colissimoStatus;

    protected $shipmentTrackCollectionFactory;

    protected $encryptor;

    public function __construct(
        Helper\Data $helperData,
        Logger\Colissimo $logger,
        ColissimoStatus $colissimoStatus,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $shipmentTrackCollectionFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->helperData = $helperData;
        $this->logger = $logger;
        $this->colissimoStatus = $colissimoStatus;
        $this->shipmentTrackCollectionFactory = $shipmentTrackCollectionFactory;
        $this->encryptor = $encryptor;
    }

    public function getCurrentState($trackingNumber, $login = null, $password = null, $storeId = null)
    {
        if (null === $login) {
            $login = $this->helperData->getAdvancedConfigValue(
                'lpc_general/id_webservices',
                $storeId
            );
        }

        if (null === $password) {
            $password = $this->helperData->getAdvancedConfigValue(
                'lpc_general/pwd_webservices',
                $storeId
            );
        }

        $this->logger->debug(
            __METHOD__ . ' request',
            [
                'url'            => self::API_BASE_URL,
                'login'          => $login,
                'trackingNumber' => $trackingNumber,
            ]
        );

        $soapClient = new \SoapClient(
            self::API_BASE_URL . '?wsdl',
            ['exceptions' => true]
        );

        $request = [
            'accountNumber' => $login,
            'password'      => $password,
            'skybillNumber' => $trackingNumber,
        ];


        $response = $soapClient->track($request);
        $response = $response->return;

        $this->logger->debug(
            __METHOD__ . ' response',
            [
                'response' => $response,
            ]
        );


        if (0 !== $response->errorCode) {
            $this->logger->error(
                __METHOD__ . ' error in API response',
                [
                    'response' => $response,
                ]
            );
            throw new \LaPoste\Colissimo\Exception\TrackingApiException($response->errorMessage, $response->errorCode);
        }

        return $response;
    }

    public function updateAllStatuses($login = null, $password = null)
    {
        $fromDate = date('Y-m-d 00:00:00', strtotime(self::UPDATE_STATUS_PERIOD));
        $collection = $this->shipmentTrackCollectionFactory->create()
                                                           ->addFilter('carrier_code', 'colissimo')
                                                           ->addAttributeToFilter('created_at', ['from' => $fromDate]);

        $result = [
            'success' => [],
            'failure' => [],
        ];
        foreach ($collection as $shipmentTrack) {
            $shipment = $shipmentTrack->getShipment();
            $order = $shipment->getOrder();

            $this->logger->debug(
                __METHOD__ . ' updating status for',
                [
                    'shipmentTrack.entity_id'    => $shipmentTrack->getEntityId(),
                    'shipmentTrack.track_number' => $shipmentTrack->getTrackNumber(),
                ]
            );

            try {
                $currentState = $this->getCurrentState(
                    $shipmentTrack->getTrackNumber(),
                    $login,
                    $password,
                    $shipment->getStoreId()
                );

                $currentStateInternalCode = $this->colissimoStatus->getInternalCodeForClp($currentState->eventCode);
                if (null === $currentStateInternalCode) {
                    $currentStateInternalCode = ColissimoStatus::UNKNOWN_STATUS_INTERNAL_CODE;
                }

                // store new state into the shipmentTrack
                $shipment->setShipmentStatus($currentStateInternalCode);
                $shipment->save();


                $currentStateInfo = $this->colissimoStatus->getStatusInfo($currentStateInternalCode);
                $change_order_status = $currentStateInfo['change_order_status'];
                if (!empty($change_order_status)) {
                    $order
                        ->setState(\Magento\Sales\Model\Order::STATE_COMPLETE)
                        ->setStatus($change_order_status)
                        ->save();
                }

                $result['success'][$shipmentTrack->getTrackNumber()] = $currentState->eventCode;
            } catch (\Exception $e) {
                $result['failure'][$shipmentTrack->getTrackNumber()] = $e->getMessage();
            }
        }

        return $result;
    }

    public function encrypt($trackNumber)
    {
        return $this->encryptor->encrypt($trackNumber);
    }

    public function decrypt($trackHash)
    {
        return $this->encryptor->decrypt($trackHash);
    }
}
