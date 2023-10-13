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
use Magento\Framework\Encryption\EncryptorInterface;
use LaPoste\Colissimo\Api\ColissimoStatus;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory;
use Magento\Sales\Api\ShipmentTrackRepositoryInterface;

class UnifiedTrackingApi implements \LaPoste\Colissimo\Api\UnifiedTrackingApi
{
    const API_BASE_URL = 'https://ws.colissimo.fr/tracking-timeline-ws/soap/tracking/TrackingTimelineServiceWS';


    const UPDATE_STATUS_PERIOD = '-15 days';

    protected $logger;

    protected $helperData;

    protected $encryptor;
    /**
     * @var \LaPoste\Colissimo\Api\ColissimoStatus
     */
    private $colissimoStatus;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory
     */
    private $shipmentTrackCollectionFactory;
    /**
     * @var \Magento\Sales\Api\ShipmentTrackRepositoryInterface
     */
    private $shipmentTrackRepository;

    /**
     * UnifiedTrackingApi constructor.
     * @param \LaPoste\Colissimo\Helper\Data                                            $helperData
     * @param \LaPoste\Colissimo\Logger\Colissimo                                       $logger
     * @param \Magento\Framework\Encryption\EncryptorInterface                          $encryptor
     * @param \LaPoste\Colissimo\Api\ColissimoStatus                                    $colissimoStatus
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $shipmentTrackCollectionFactory
     * @param \Magento\Sales\Api\ShipmentTrackRepositoryInterface                       $shipmentTrackRepository
     */
    public function __construct(
        Helper\Data $helperData,
        Logger\Colissimo $logger,
        EncryptorInterface $encryptor,
        ColissimoStatus $colissimoStatus,
        CollectionFactory $shipmentTrackCollectionFactory,
        ShipmentTrackRepositoryInterface $shipmentTrackRepository
    ) {
        $this->helperData = $helperData;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
        $this->colissimoStatus = $colissimoStatus;
        $this->shipmentTrackCollectionFactory = $shipmentTrackCollectionFactory;
        $this->shipmentTrackRepository = $shipmentTrackRepository;
    }

    public function getTrackingInfo(
        $trackingNumber,
        $ip,
        $lang = null,
        $login = null,
        $password = null,
        $storeId = null
    ) {
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

        if (null === $lang) {
            $lang = 'fr_FR';
        }

        $this->logger->debug(
            __METHOD__ . ' request',
            [
                'url'            => self::API_BASE_URL,
                'login'          => $login,
                'trackingNumber' => $trackingNumber,
                'lang'           => $lang,
                'ip'             => $ip,
            ]
        );

        $soapClient = new \SoapClient(
            self::API_BASE_URL . '?wsdl',
            ['exceptions' => true]
        );

        $request = [
            'login'        => $login,
            'password'     => $password,
            'parcelNumber' => $trackingNumber,
            'ip'           => $ip,
            'lang'         => $lang,
            'profil'       => 'TRACKING_PARTNER',
        ];


        $response = $soapClient->timelineCompany($request);
        $response = $response->return;

        $this->logger->debug(
            __METHOD__ . ' response',
            [
                'response' => $response,
            ]
        );

        if (0 != $response->status->code) {
            $this->logger->error(
                __METHOD__ . ' error in API response',
                [
                    'response' => $response,
                ]
            );
            throw new \LaPoste\Colissimo\Exception\TrackingApiException(
                $response->status->message,
                $response->status->code
            );
        }

        if (!is_array($response->parcel->event)) {
            $response->parcel->event = [$response->parcel->event];
        }

        // Sort events by date
        usort($response->parcel->event,
            function ($a, $b) {
                return strtotime($a->date) > strtotime($b->date);
            }
        );

        return $response;
    }

    public function updateAllStatuses($login = null, $password = null, $ip = null, $lang = null)
    {
        $fromDate = date('Y-m-d 00:00:00', strtotime(self::UPDATE_STATUS_PERIOD));
        $collection = $this->shipmentTrackCollectionFactory->create()
                                                           ->addFilter('carrier_code', 'colissimo')
                                                           ->addFilter('lpc_is_delivered', '0')
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
                $currentState = $this->getTrackingInfo(
                    $shipmentTrack->getTrackNumber(),
                    $ip,
                    $lang,
                    $login,
                    $password,
                    $shipment->getStoreId()
                );

                $lastEvent = end($currentState->parcel->event);
                $eventLastCode = $lastEvent->code;
                $currentStateInternalCode = $this->colissimoStatus->getInternalCodeForClp($eventLastCode);
                if (null === $currentStateInternalCode) {
                    $currentStateInternalCode = ColissimoStatus::UNKNOWN_STATUS_INTERNAL_CODE;
                }

                // store new state into the shipmentTrack
                $shipment->setShipmentStatus($currentStateInternalCode);
                $shipment->save();

                // Save last event info into the shipment track
                $lastEvent = end($currentState->parcel->event);
                $eventLastCode = $lastEvent->code;
                $eventLastDate = $lastEvent->date;
                $shipmentTrack->setLpcLastEventCode($eventLastCode);
                $shipmentTrack->setLpcLastEventDate($eventLastDate);
                $shipmentTrack->setLpcIsDelivered(!isset($currentState->parcel->statusDelivery) || !$currentState->parcel->statusDelivery ? '0' : '1');
                $this->shipmentTrackRepository->save($shipmentTrack);


                $currentStateInfo = $this->colissimoStatus->getStatusInfo($currentStateInternalCode);
                $change_order_status = $currentStateInfo['change_order_status'];
                $change_order_state = $currentStateInfo['change_order_state'];
                if (!empty($change_order_status) && !empty($change_order_state)) {
                    $order
                        ->setState($change_order_state)
                        ->setStatus($change_order_status)
                        ->save();
                }

                $result['success'][$shipmentTrack->getTrackNumber()] = $eventLastCode;
            } catch (\Exception $e) {

                $this->logger->error(__METHOD__ . " can't update status", [
                    'shipmentTrack.entity_id'    => $shipmentTrack->getEntityId(),
                    'shipmentTrack.track_number' => $shipmentTrack->getTrackNumber(),
                    'errorMessage'               => $e->getMessage(),
                ]);

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
