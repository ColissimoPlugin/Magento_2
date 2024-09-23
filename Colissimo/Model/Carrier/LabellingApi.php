<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Model\Carrier;

use LaPoste\Colissimo\Exception;
use LaPoste\Colissimo\Exception\ApiException;
use Magento\Framework\Event\Manager;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Model\Order;
use LaPoste\Colissimo\Helper\Data;
use Magento\Framework\Url;
use LaPoste\Colissimo\Api\TrackingApi;

class LabellingApi implements \LaPoste\Colissimo\Api\Carrier\LabellingApi
{
    const API_BASE_URL = 'https://ws.colissimo.fr/sls-ws/SlsServiceWSRest/2.0/';

    /**
     * @var \LaPoste\Colissimo\Logger\Colissimo
     */
    protected $logger;
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $orderModel;
    /**
     * @var \LaPoste\Colissimo\Helper\Data
     */
    protected $helperData;
    /**
     * @var \Magento\Framework\Url
     */
    protected $urlHelper;
    /**
     * @var \LaPoste\Colissimo\Api\TrackingApi
     */
    protected $trackingApi;
    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;


    /**
     * LabellingApi constructor.
     * @param \LaPoste\Colissimo\Logger\Colissimo               $logger
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Sales\Model\Order                        $orderModel
     * @param \LaPoste\Colissimo\Helper\Data                    $helperData
     * @param \Magento\Framework\Url                            $urlHelper
     * @param \LaPoste\Colissimo\Api\TrackingApi                $trackingApi
     * @param \Magento\Framework\Event\Manager                  $eventManager
     */
    public function __construct(
        \LaPoste\Colissimo\Logger\Colissimo $logger,
        TransportBuilder $transportBuilder,
        Order $orderModel,
        Data $helperData,
        Url $urlHelper,
        TrackingApi $trackingApi,
        Manager $eventManager
    ) {
        $this->logger = $logger;
        $this->transportBuilder = $transportBuilder;
        $this->orderModel = $orderModel;
        $this->helperData = $helperData;
        $this->urlHelper = $urlHelper;
        $this->trackingApi = $trackingApi;
        $this->eventManager = $eventManager;
    }

    /**
     * Return the URL of the given action in the Colissimo Api.
     * @param $action
     * @return string
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
     * @param string $action
     * @param        $responseHandler
     * @param array  $params
     * @return mixed
     * @throws ApiException
     */
    protected function query(string $action, $responseHandler, array $params = [])
    {
        $dataJson = $this->json_encode($params);

        $url = $this->getApiUrl($action);

        $this->logger->debug(__METHOD__, ['url' => $url]);

        $headers = ['Content-Type: application/json'];
        if ('api' === $this->helperData->getAdvancedConfigValue('lpc_general/connectionMode')) {
            $headers[] = 'apiKey: ' . $this->helperData->getAdvancedConfigValue('lpc_general/api_key');
        }

        $ch = curl_init();
        curl_setopt_array(
            $ch,
            [
                CURLOPT_URL            => $url,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POST           => 1,
                CURLOPT_POSTFIELDS     => $dataJson,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_BINARYTRANSFER => 1,
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

        return $responseHandler($ch, $response);
    }

    /**
     * @param $body
     * @return array
     */
    public function parseMultipartBody($body)
    {
        $parts = [];

        preg_match('/--(.*)\b/', $body, $boundary);

        if (!empty($boundary)) {
            $messages = array_filter(
                array_map(
                    'trim',
                    explode($boundary[0], $body)
                )
            );


            foreach ($messages as $message) {
                if ('--' === $message) {
                    break;
                }


                $headers = [];
                [$headerLines, $body] = explode("\r\n\r\n", $message, 2);

                foreach (explode("\r\n", $headerLines) as $headerLine) {
                    [$key, $value] = preg_split('/:\s+/', $headerLine, 2);
                    $headers[strtolower($key)] = $value;
                }

                $parts[$headers['content-id']] = [
                    'headers' => $headers,
                    'body'    => $body,
                ];
            }
        }

        return $parts;
    }

    /**
     * @param $ch
     * @param $response
     * @return array
     * @throws \LaPoste\Colissimo\Exception\ApiException
     */
    protected function handleMultipartResponse($ch, $response)
    {
        $returnStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        switch ($returnStatus) {
            case 200:
                curl_close($ch);

                $parts = $this->parseMultipartBody($response);

                if (!empty($parts['<jsonInfos>']) && !empty($parts['<label>'])) {
                    $jsonInfos = $parts['<jsonInfos>']['body'];
                    $label = $parts['<label>']['body'];

                    $cn23 = null;
                    if (!empty($parts['<cn23>']['body'])) {
                        $cn23 = $parts['<cn23>']['body'];
                    }

                    return [json_decode($jsonInfos), $label, $cn23];
                } else {
                    throw new Exception\ApiException(
                        'Bad response format',
                        Exception\ApiException::BAD_RESPONSE_FORMAT
                    );
                }
                break;

            default:
                curl_close($ch);
                $parts = $this->parseMultipartBody($response);

                $body = empty($parts['<jsonInfos>']) ? 0 : json_decode($parts['<jsonInfos>']['body']);

                if (!empty($body)) {
                    $messageType = $body->messages[0]->type;
                    $messageContent = $body->messages[0]->messageContent;

                    $errorMessage = 'Colissimo ' . $messageType . ' : ' . $messageContent;

                    $loggerInfo = [
                        'returnStatus'   => $returnStatus,
                        'messageType'    => $messageType,
                        'messageContent' => $messageContent,
                    ];
                } else {
                    $errorMessage = 'CURL error:' . $response;

                    $loggerInfo = [
                        'returnStatus' => $returnStatus,
                    ];
                }

                $this->logger->warning(
                    __METHOD__,
                    $loggerInfo
                );

                throw new Exception\ApiException($errorMessage, $returnStatus);
        }
    }

    /**
     * @param $ch
     * @param $response
     * @return mixed
     * @throws \LaPoste\Colissimo\Exception\ApiException
     */
    protected function handleCheckGenerateLabelResponse($ch, $response)
    {
        curl_close($ch);
        $responseDecode = json_decode($response);

        if (isset($responseDecode->messages[0]) && isset($responseDecode->messages[0]->id) && isset($responseDecode->messages[0]->messageContent)) {
            return $responseDecode;
        } else {
            throw new Exception\ApiException(
                'Bad response format',
                Exception\ApiException::BAD_RESPONSE_FORMAT
            );
        }
    }

    /**
     * @param $ch
     * @param $response
     * @return mixed
     * @throws \LaPoste\Colissimo\Exception\ApiException
     */
    protected function handleMonopartResponse($ch, $response)
    {
        $returnStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        switch ($returnStatus) {
            case 200:
                $this->logger->debug(__METHOD__, ['response' => $response]);
                curl_close($ch);

                return json_decode($response);

            default:
                curl_close($ch);
                $this->logger->warning(
                    __METHOD__,
                    [
                        'returnStatus' => $returnStatus,
                    ]
                );
                throw new Exception\ApiException('CURL error: ' . $response, $returnStatus);
        }
    }

    /**
     * @param \LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload $payload
     * @return array|mixed
     * @throws \LaPoste\Colissimo\Exception\ApiException
     */
    public function generateLabel(\LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload $payload, bool $isSecuredReturn = false)
    {
        $this->logger->debug('Label generation query', ['payload' => $payload->getPayloadWithoutPassword(), 'method' => __METHOD__]);
        $currentPayload = $payload->assemble();
        $queryAction = $isSecuredReturn ? 'generateToken' : 'generateLabel';
        $res = $this->query($queryAction, [$this, 'handleMultipartResponse'], $currentPayload);
        $this->logger->debug('Label generation response', ['response' => $res[0], 'method' => __METHOD__]);
        if (empty($currentPayload['letter']['service']['orderNumber'])) {
            $this->logger->error('Error while generating label: order ID not found.');

            return $res;
        }

        if ($isSecuredReturn) {
            return $res;
        }
        $orderId = $currentPayload['letter']['service']['orderNumber'];
        // Return label : send it to customer. Else send tracking link
        if ($payload->getIsReturnLabel()) {
            $this->eventManager->dispatch(
                'lpc_generate_inward_label_after',
                [
                    'orderIds' => [$orderId],
                    'label'    => $res[1],
                ]
            );
        } else {
            $this->eventManager->dispatch(
                'lpc_generate_outward_label_after',
                [
                    'orderIds'     => [$orderId],
                    'label'        => $res[1],
                    'parcelNumber' => [$orderId => $res[0]->labelV2Response->parcelNumber],
                ]
            );
        }

        return $res;
    }

    /**
     * @param \LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload $payload
     * @return mixed
     * @throws \LaPoste\Colissimo\Exception\ApiException
     */
    public function checkGenerateLabel(\LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload $payload)
    {
        $this->logger->debug('Check generate label query', ['payload' => $payload->getPayloadWithoutPassword(), 'method' => __METHOD__]);
        $res = $this->query('checkGenerateLabel', [$this, "handleCheckGenerateLabelResponse"], $payload->assemble());
        $this->logger->debug('Check generate response', ['response' => $res[0], 'method' => __METHOD__]);

        return $res;
    }

    /**
     * @param array $payload
     * @return mixed
     * @throws Exception\ApiException
     */
    public function listMailBoxPickingDates(array $payload)
    {
        $payloadWithoutPass = $payload;
        unset($payloadWithoutPass['password']);
        $this->logger->debug('List mail box picking dates query', ['payload' => $payloadWithoutPass, 'method' => __METHOD__]);
        $res = $this->query('getListMailBoxPickingDates', [$this, 'handleMonopartResponse'], $payload);
        $this->logger->debug('List mail box picking dates response', ['response' => $res, 'method' => __METHOD__]);

        return $res;
    }

    /**
     * @param array $payload
     * @return array
     */
    public function planPickup(array $payload)
    {
        return ['id' => 0, 'messageContent' => 'by-passed for tests']; //DEV_CODE

        $payloadWithoutPass = $payload;
        unset($payloadWithoutPass['password']);
        $this->logger->debug('Plan pickup query', ['payload' => $payloadWithoutPass, 'method' => __METHOD__]);
        $res = $this->query('planPickup', [$this, 'handleMonopartResponse'], $payload);
        $this->logger->debug('Plan pickup response', ['response' => $res, 'method' => __METHOD__]);

        return $res;
    }

    private function json_encode($data)
    {
        $json = json_encode($data);

        // If json_encode() was successful, no need to do more sanity checking.
        if (false !== $json) {
            return $json;
        }

        try {
            $data = $this->json_sanity_check($data, 512);
        } catch (\Exception $e) {
            return false;
        }

        return json_encode($data);
    }

    private function json_sanity_check($data, $depth)
    {
        if ($depth < 0) {
            throw new \Exception('Reached depth limit');
        }

        if (is_array($data)) {
            $output = [];
            foreach ($data as $id => $el) {
                // Don't forget to sanitize the ID!
                if (is_string($id)) {
                    $clean_id = $this->json_convert_string($id);
                } else {
                    $clean_id = $id;
                }

                // Check the element type, so that we're only recursing if we really have to.
                if (is_array($el) || is_object($el)) {
                    $output[$clean_id] = $this->json_sanity_check($el, $depth - 1);
                } elseif (is_string($el)) {
                    $output[$clean_id] = $this->json_convert_string($el);
                } else {
                    $output[$clean_id] = $el;
                }
            }
        } elseif (is_object($data)) {
            $output = new \stdClass();
            foreach ($data as $id => $el) {
                if (is_string($id)) {
                    $clean_id = $this->json_convert_string($id);
                } else {
                    $clean_id = $id;
                }

                if (is_array($el) || is_object($el)) {
                    $output->$clean_id = $this->json_sanity_check($el, $depth - 1);
                } elseif (is_string($el)) {
                    $output->$clean_id = $this->json_convert_string($el);
                } else {
                    $output->$clean_id = $el;
                }
            }
        } elseif (is_string($data)) {
            return $this->json_convert_string($data);
        } else {
            return $data;
        }

        return $output;
    }

    private function json_convert_string($string)
    {
        static $use_mb = null;
        if (is_null($use_mb)) {
            $use_mb = function_exists('mb_convert_encoding');
        }

        if ($use_mb) {
            $encoding = mb_detect_encoding($string, mb_detect_order(), true);
            if ($encoding) {
                return mb_convert_encoding($string, 'UTF-8', $encoding);
            } else {
                return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
            }
        } else {
            return $this->check_invalid_utf8($string, true);
        }
    }

    private function check_invalid_utf8($string, $strip = false)
    {
        $string = (string) $string;

        if (0 === strlen($string)) {
            return '';
        }

        // Check for support for utf8 in the installed PCRE library once and store the result in a static.
        static $utf8_pcre = null;
        if (!isset($utf8_pcre)) {
            set_error_handler(function () {
            });
            $utf8_pcre = preg_match('/^./u', 'a');
            restore_error_handler();
        }
        // We can't demand utf8 in the PCRE installation, so just return the string in those cases.
        if (!$utf8_pcre) {
            return $string;
        }

        // preg_match fails when it encounters invalid UTF8 in $string.
        set_error_handler(function () {
        });
        $lonose = preg_match('/^./us', $string);
        restore_error_handler();
        if (1 === $lonose) {
            return $string;
        }

        // Attempt to strip the bad chars if requested (not recommended).
        if ($strip && function_exists('iconv')) {
            return iconv('utf-8', 'utf-8', $string);
        }

        return '';
    }
}
