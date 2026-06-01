<?php

namespace LaPoste\Colissimo\Model\RelaysWebservice;

use LaPoste\Colissimo\Logger\Colissimo;
use SoapClient;

class RelaysApi extends SoapClient implements \LaPoste\Colissimo\Api\RelaysWebservice\RelaysApi
{
    const API_RELAYS_WSDL_URL = 'https://ws.colissimo.fr/pointretrait-ws-cxf/PointRetraitServiceWS/2.0?wsdl';

    public Colissimo $logger;

    public function __construct(Colissimo $logger, ?array $options = null)
    {
        $this->logger = $logger;

        $options = is_null($options) ? ['trace' => true] : $options;

        parent::__construct(self::API_RELAYS_WSDL_URL, $options);
    }

    protected function query($params)
    {
        return $this->__soapCall('findRDVPointRetraitAcheminement', [$params]);
    }

    public function getRelays($params)
    {
        $paramsWithoutCredentials = $params;

        unset($paramsWithoutCredentials['apikey']);
        unset($paramsWithoutCredentials['password']);

        $this->logger->debug(
            'Get relays',
            [
                'params'       => $paramsWithoutCredentials,
                'wsdlUrl'      => self::API_RELAYS_WSDL_URL,
                'functionName' => 'findRDVPointRetraitAcheminement',
                'method'       => __METHOD__,
            ]
        );

        $response = $this->query($params);

        $this->logger->debug('Get relays response', ['response' => $response, 'method' => __METHOD__]);

        return $response;
    }
}
