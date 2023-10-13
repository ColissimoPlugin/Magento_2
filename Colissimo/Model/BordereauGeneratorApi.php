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

use Magento\Framework\Event\Manager;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory;

class BordereauGeneratorApi implements \LaPoste\Colissimo\Api\BordereauGeneratorApi
{
    const API_BASE_URL = 'https://ws.colissimo.fr/sls-ws/SlsServiceWS/2.0';

    protected $logger;

    protected $helperData;

    protected $eventManager;

    private $shipmentTrackCollectionFactory;

    public function __construct(
        \LaPoste\Colissimo\Helper\Data $helperData,
        \LaPoste\Colissimo\Logger\Colissimo $logger,
        Manager $eventManager,
        CollectionFactory $shipmentTrackCollectionFactory
    ) {
        $this->helperData = $helperData;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->shipmentTrackCollectionFactory = $shipmentTrackCollectionFactory;
    }


    public function generateBordereauByParcelsNumbers(
        array $parcelNumbers,
        $login = null,
        $password = null
    ) {
        if (null === $login) {
            $login = $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices');
        }

        if (null === $password) {
            $password = $this->helperData->getAdvancedConfigValue('lpc_general/pwd_webservices');
        }

        $this->logger->debug(
            __METHOD__ . ' request',
            [
                'url'           => self::API_BASE_URL,
                'login'         => $login,
                'parcelNumbers' => $parcelNumbers,
            ]
        );


        $soapClient = new \LaPoste\Colissimo\Helper\LpcMTOMSoapClient(
            self::API_BASE_URL . '?wsdl',
            ['exceptions' => true]
        );

        $request = [
            'contractNumber'                    => $login,
            'password'                          => $password,
            'generateBordereauParcelNumberList' => $parcelNumbers,
        ];


        $response = $soapClient->generateBordereauByParcelsNumbers($request);
        $response = $response->return;

        $this->logger->debug(
            __METHOD__ . ' response',
            [
                'response' => $response->messages,
            ]
        );


        if (!empty($response->messages->id)) {
            $this->logger->error(
                __METHOD__ . ' error in API response',
                [
                    'response' => $response->messages,
                ]
            );
            throw new \LaPoste\Colissimo\Exception\BordereauGeneratorApiException(
                $response->messages->messageContent,
                $response->messages->id
            );
        }

        $collection = $this->shipmentTrackCollectionFactory->create()
                                                           ->addFilter('carrier_code', 'colissimo')
                                                           ->addAttributeToFilter('track_number', ['in' => $parcelNumbers]);

        $orderIds = [];
        $trackingNumbers = [];
        foreach ($collection as $shipmentTrack) {
            $orderId = $shipmentTrack->getShipment()->getOrder()->getIncrementId();
            $orderIds[] = $orderId;
            $trackingNumbers[$orderId] = $shipmentTrack->getTrackNumber();
        }
        $this->eventManager->dispatch(
            'lpc_generate_bordereau_after',
            [
                'orderIds'     => $orderIds,
                'parcelNumber' => $trackingNumbers,
            ]
        );


        return $response;
    }


    public function getBordereauByNumber(
        $bordereauNumber,
        $login = null,
        $password = null
    ) {
        if (null === $login) {
            $login = $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices');
        }

        if (null === $password) {
            $password = $this->helperData->getAdvancedConfigValue('lpc_general/pwd_webservices');
        }

        $this->logger->debug(
            __METHOD__ . ' request',
            [
                'url'             => self::API_BASE_URL,
                'login'           => $login,
                'bordereauNumber' => $bordereauNumber,
            ]
        );

        $soapClient = new \LaPoste\Colissimo\Helper\LpcMTOMSoapClient(
            self::API_BASE_URL . '?wsdl',
            ['exceptions' => true]
        );

        $request = [
            'contractNumber'  => $login,
            'password'        => $password,
            'bordereauNumber' => $bordereauNumber,
        ];


        $response = $soapClient->getBordereauByNumber($request);
        $response = $response->return;

        $this->logger->debug(
            __METHOD__ . ' response',
            [
                'response' => $response->messages,
            ]
        );


        if (!empty($response->messages->id)) {
            $this->logger->error(
                __METHOD__ . ' error in API response',
                [
                    'response' => $response->messages,
                ]
            );
            throw new \LaPoste\Colissimo\Exception\TrackingApiException(
                $response->messages->messageContent,
                $response->messages->id
            );
        }

        return $response;
    }
}
