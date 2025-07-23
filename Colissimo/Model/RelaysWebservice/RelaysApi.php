<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Model\RelaysWebservice;

use SoapClient;

class RelaysApi extends SoapClient implements \LaPoste\Colissimo\Api\RelaysWebservice\RelaysApi
{
    const API_RELAYS_WSDL_URL = "https://ws.colissimo.fr/pointretrait-ws-cxf/PointRetraitServiceWS/2.0?wsdl";

    public $logger;

    public function __construct(\LaPoste\Colissimo\Logger\Colissimo $logger, ?array $options = null)
    {
        $this->logger = $logger;

        $options = is_null($options) ? ['trace' => true] : $options;

        parent::__construct(self::API_RELAYS_WSDL_URL, $options);
    }

    protected function query($params)
    {
        return $this->__soapCall("findRDVPointRetraitAcheminement", [$params]);
    }

    public function getRelays($params)
    {
        $paramsWithoutPass = $params;

        unset($paramsWithoutPass['password']);

        $this->logger->debug('Get relays',
                             ['params' => $paramsWithoutPass, 'wsdlUrl' => self::API_RELAYS_WSDL_URL, 'functionName' => 'findRDVPointRetraitAcheminement', 'method' => __METHOD__]);

        $response = $this->query($params);

        $this->logger->debug('Get relays response', ['response' => $response, 'method' => __METHOD__]);

        return $response;
    }
}
