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
    const API_BASE_URL = 'https://ws.colissimo.fr/api-ewe/v1/rest/';
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

    public function getAutologinURLs(): array
    {
        try {
            $response = $this->query('urlCboxExt');

            if (!empty($response['messageErreur'])) {
                $this->logger->error(
                    'Auto login request failed',
                    [
                        'method' => __METHOD__,
                        'error'  => $response['messageErreur'],
                    ]
                );

                return [];
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Auto login request failed',
                [
                    'method' => __METHOD__,
                    'error'  => $e->getMessage(),
                ]
            );

            return [];
        }

        return $response;
    }

    public function isCgvAccepted(): bool
    {
        $markers = $this->helperData->getMarkers();

        if (!empty($markers['contractType']) && self::CONTRACT_TYPE_FACILITE !== $markers['contractType']) {
            return true;
        }

        if (!empty($markers['acceptedCgv'])) {
            return true;
        }

        // Get contract type
        $accountInformation = $this->getAccountInformation();

        // We couldn't get the account information, we can't check the CGV
        if (empty($accountInformation)) {
            return true;
        }

        if (self::CONTRACT_TYPE_FACILITE !== $accountInformation['contractType'] || !empty($accountInformation['cgv']['accepted'])) {
            $this->helperData->setMarker('contractType', $accountInformation['contractType']);
            $this->helperData->setMarker('acceptedCgv', !empty($accountInformation['cgv']['accepted']));

            return true;
        }

        return false;
    }

    public function getAccountInformation()
    {
        try {
            $response = $this->query('additionalinformations');

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

        if (empty($response['cgv'])) {
            return false;
        }

        return $response;
    }

    public function query(
        $action,
        $params = [],
        $dataType = self::DATA_TYPE_JSON,
        $credentials = [],
        $credentialsIntoHeader = false,
        $unsafeFileUpload = false,
        $throwError = true
    ) {
        if ('api' === $this->helperData->getAdvancedConfigValue('lpc_general/connectionMode')) {
            $params['credential']['apiKey'] = $this->helperData->getAdvancedConfigValue('lpc_general/api_key');
        } else {
            $params['credential']['login'] = $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices');
            $params['credential']['password'] = $this->helperData->getAdvancedConfigValue('lpc_general/pwd_webservices');
        }

        $parentAccountId = $this->helperData->getAdvancedConfigValue('lpc_general/parent_id_webservices');
        if (!empty($parentAccountId)) {
            $params['partnerClientCode'] = $parentAccountId;
        }

        $params['tagInfoPartner'] = 'MAGENTO2';

        return parent::query(
            $action,
            $params,
            $dataType,
            $credentials,
            $credentialsIntoHeader,
            $unsafeFileUpload,
            $throwError
        );
    }
}
