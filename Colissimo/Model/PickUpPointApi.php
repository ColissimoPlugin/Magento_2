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

class PickUpPointApi implements \LaPoste\Colissimo\Api\PickUpPointApi
{
    const API_BASE_URL = 'https://ws.colissimo.fr/widget-colissimo/rest/';

    protected $logger;

    protected $helperData;

    public $token;

    public function __construct(
        Helper\Data $helperData,
        Logger\Colissimo $logger
    ) {
        $this->helperData = $helperData;
        $this->logger = $logger;
    }

    /**
     * Return the URL of the given action in the Colissimo Api.
     */
    protected function getApiUrl($action)
    {
        return self::API_BASE_URL . $action;
    }

    /**
     * Execute a query against the Colissimo Api for the given action, using the given params.
     * It will return an object with the deserialized JSON value.
     *
     * Will throw \LaPoste\Colissimo\Exception\ApiException if response is not 200.
     */
    protected function query($action, $params = [])
    {
        $data = json_encode($params);

        $url = $this->getApiUrl($action);

        $this->logger->debug(__METHOD__, ['url' => $url, 'login' => $params['login']]);

        $ch = curl_init();
        curl_setopt_array(
            $ch,
            [
                CURLOPT_URL            => $url,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_POST           => 1,
                CURLOPT_POSTFIELDS     => $data,
                CURLOPT_RETURNTRANSFER => true,
            ]
        );

        $response = curl_exec($ch);
        if (!$response) {
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            $this->logger->error(__METHOD__, [
                'curl_errno' => $curlErrno,
                'curl_error' => $curlError,
            ]);
            curl_close($ch);
            throw new Exception\ApiException($curlError, $curlErrno);
        }

        $returnStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        switch ($returnStatus) {
            case 200:
                $this->logger->debug(__METHOD__, ['response' => $response]);
                curl_close($ch);

                return json_decode($response);

            default:
                curl_close($ch);
                $this->logger->warning(__METHOD__, [
                    'returnStatus' => $returnStatus,
                ]);
                throw new Exception\ApiException(null, $returnStatus);
        }
    }

    public function authenticate($login = null, $password = null)
    {
        if (null === $login) {
            $login = $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices');
        }

        if (null === $password) {
            $password = $this->helperData->getAdvancedConfigValue('lpc_general/pwd_webservices');
        }

        $this->logger->debug(__METHOD__, ['login' => $login]);

        try {
            $response = $this->query(
                'authenticate.rest',
                [
                    'login'    => $login,
                    'password' => $password,
                ]
            );
        } catch (Exception\ApiException $e) {
            $this->logger->error("Error during authentication. Check your credentials.", [$e]);

            return false;
        }

        $this->logger->debug(__METHOD__, ['response' => $response]);


        if (!empty($response->token)) {
            $this->token = $response->token;
        }

        return $response;
    }
}
