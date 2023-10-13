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

use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Logger\Colissimo;

class AccountApi extends RestApi implements \LaPoste\Colissimo\Api\AccountApi
{
    const API_BASE_URL = 'https://ws.colissimo.fr/api-ewe/';
    const CONTRACT_TYPE_FACILITE = 'FACILITE';

    protected $logger;
    protected $helperData;

    public function __construct(
        Data $helperData,
        Colissimo $logger
    ) {
        $this->helperData = $helperData;
        $this->logger = $logger;
    }

    protected function getApiUrl($action)
    {
        return self::API_BASE_URL . $action;
    }

    public function isCgvAccepted(): bool
    {
        $login = $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices');
        $password = $this->helperData->getAdvancedConfigValue('lpc_general/pwd_webservices');

        if (empty($login) || empty($password)) {
            return true;
        }

        // Get contract type
        $accountInformation = $this->getCgvInformation($login, $password);

        // We couldn't get the account information, we can't check the CGV
        if (empty($accountInformation)) {
            return true;
        }

        if (self::CONTRACT_TYPE_FACILITE !== $accountInformation['contractType'] || !empty($accountInformation['cgv']['accepted'])) {
            return true;
        }

        return false;
    }

    public function getCgvInformation(string $login, string $password)
    {
        $payload = [
            'credential' => [
                'login' => $login,
                'password' => $password,
            ],
        ];

        try {
            $response = $this->query('v1/rest/additionalinformations', $payload);

            if (!empty($response['messageErreur'])) {
                $this->logger->error(
                    __METHOD__,
                    [
                        'error' => $response['messageErreur'],
                    ]
                );

                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__,
                [
                    'error' => $e->getMessage(),
                ]
            );

            return false;
        }

        $this->logger->debug(
            __METHOD__,
            [
                'response' => $response,
            ]
        );

        if (empty($response['contractType'])) {
            return false;
        }

        return $response;
    }
}
