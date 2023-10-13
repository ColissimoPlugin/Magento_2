<?php

namespace LaPoste\Colissimo\Model\Carrier;

use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Logger;
use LaPoste\Colissimo\Exception;


class OffersApi implements \LaPoste\Colissimo\Api\Carrier\OffersApi
{

    const API_BASE_URL = "https://ws.colissimo.fr/eligibility-ws/GetOffersServiceWS";

    public $helperData;

    public $logger;

    public function __construct(Data $helperData, Logger\Colissimo $logger)
    {
        $this->helperData = $helperData;
        $this->logger = $logger;
    }

    public function getColissimoOffers($addressee, $depositDate, $login = null, $password = null)
    {
        if (null === $login) {
            $login = $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices');
        }

        if (null === $password) {
            $password = $this->helperData->getAdvancedConfigValue('lpc_general/pwd_webservices');
        }

        $this->logger->debug(
            __METHOD__ . ' request',
            [
                'url'         => self::API_BASE_URL,
                'login'       => $login,
                'addressee'   => $addressee,
                'depositDate' => $depositDate,
            ]
        );

        $soapClient = new \SoapClient(
            self::API_BASE_URL . '?wsdl', ['exceptions' => true]
        );

        $request = [
            "contractNumber" => $login,
            "password"       => $password,
            "addressee"      => $addressee,
            "depositDate"    => $depositDate,
        ];

        $fullResponse = $soapClient->getOffers($request);
        $response = $fullResponse->result->message;

        $this->logger->debug(
            __METHOD__ . ' response',
            [
                'response' => $response,
            ]
        );

        if (0 !== $response->code) {
            $this->logger->error(
                __METHOD__ . ' error in API response',
                [
                    'response' => $response,
                ]
            );

            throw new Exception\ApiException($response->label, $response->code);
        }

        return $fullResponse->result;
    }
}
