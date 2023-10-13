<?php

namespace LaPoste\Colissimo\Model\Carrier;


use LaPoste\Colissimo\Model\RestApi;

class CustomsDocumentsApi extends RestApi implements \LaPoste\Colissimo\Api\Carrier\CustomsDocumentsApi
{
    const API_BASE_URL = 'https://ws.colissimo.fr/api-document/rest/';

    protected function getApiUrl($action)
    {
        return self::API_BASE_URL . $action;
    }

    public function storeDocument($documentType, $parcelNumber, $document, $documentName)
    {
        $accountNumber = $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices');
        $login = $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices');
        $password = $this->helperData->getAdvancedConfigValue('lpc_general/pwd_webservices');

        if (function_exists('curl_file_create')) {
            $document = curl_file_create($document);
            $unsafeFileUpload = false;
        } else {
            $document = '@' . realpath($document);
            $unsafeFileUpload = true;
        }

        $payload = [
            'accountNumber' => $accountNumber,
            'parcelNumber'  => $parcelNumber,
            'documentType'  => $documentType,
            'file'          => $document,
            'filename'      => $parcelNumber . '-' . $documentType . '.' . pathinfo($documentName, PATHINFO_EXTENSION),
        ];

        $this->logger->debug(
            'Customs Documents Sending Request',
            [
                'method'  => __METHOD__,
                'payload' => $payload,
            ]
        );

        $credentials = ['login: ' . $login, 'password: ' . $password];

        try {
            $response = $this->query('storedocument', $payload, self::DATA_TYPE_MULTIPART, $credentials, true, $unsafeFileUpload);

            $this->logger->debug(
                'Customs Documents Sending Response',
                [
                    'method'   => __METHOD__,
                    'response' => $response,
                ]
            );

            if ('000' != $response['errorCode']) {
                throw new \Exception($response['errors']['code'] . ' - ' . $response['errorLabel'] . ': ' . $response['errors']['message']);
            }

            // 50c82f93-015f-3c41-a841-07746eee6510.pdf for example, where 50c82f93-015f-3c41-a841-07746eee6510 is the uuid
            return $response['documentId'];
        } catch (\Exception $e) {
            $message = [$e->getMessage()];

            if (!empty($this->lastResponse)) {
                $this->lastResponse = json_decode($this->lastResponse, true);
                if (!empty($this->lastResponse['errors'])) {
                    foreach ($this->lastResponse['errors'] as $oneError) {
                        $message[] = $oneError['code'] . ': ' . $oneError['message'];
                    }
                }
            }

            $this->logger->error(
                'Error during customs documents sending',
                [
                    'payload'   => $payload,
                    'login'     => $login,
                    'exception' => implode(', ', $message),
                ]
            );

            if (1 < count($message)) {
                array_shift($message);
            }

            throw new \Exception(sprintf(__('An error occurred when transmitting the file %1$s: %2$s'), $documentName, implode(', ', $message)));
        }
    }

    public function getDocuments($parcelNumber)
    {
        $login = $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices');
        $password = $this->helperData->getAdvancedConfigValue('lpc_general/pwd_webservices');

        $payload = [
            'credential' => [
                'login'    => $login,
                'password' => $password,
            ],
            'cab'        => $parcelNumber,
        ];

        $this->logger->debug(
            'Customs Documents Get Request',
            [
                'method'  => __METHOD__,
                'payload' => $payload,
            ]
        );

        try {
            $response = $this->query(
                'documents',
                $payload,
                self::DATA_TYPE_JSON,
                [],
                false,
                false,
                false
            );

            $this->logger->debug(
                'Customs Documents Get Response',
                [
                    'method'   => __METHOD__,
                    'response' => $response,
                ]
            );

            if (!in_array($response['errorCode'], ['000', '003'])) {
                return [
                    'status'  => 'error',
                    'message' => $response['errorCode'] . ' - ' . $response['errorLabel'],
                ];
            }

            $response['status'] = 'success';

            return $response;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error during customs documents get',
                [
                    'payload'   => $payload,
                    'login'     => $login,
                    'exception' => $e->getMessage(),
                ]
            );

            return [
                'status'  => 'error',
                'message' => sprintf(__('An error occurred while getting the customs files for this order: %s'), $e->getMessage()),
            ];
        }
    }
}
