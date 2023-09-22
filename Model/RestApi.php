<?php

namespace LaPoste\Colissimo\Model;


use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Logger;

abstract class RestApi
{
    const DATA_TYPE_JSON = 'json';
    const DATA_TYPE_URL = 'url';
    const DATA_TYPE_MULTIPART = 'multipart';
    /** @var bool|string */
    protected $lastResponse;

    protected $helperData;
    protected $logger;

    public function __construct(Data $helperData, Logger\Colissimo $logger)
    {
        $this->helperData = $helperData;
        $this->logger = $logger;
    }

    abstract protected function getApiUrl($action);

    /**
     * @param string $action
     * @param array  $params
     * @param string $dataType
     * @param array  $credentials
     * @param false  $credentialsIntoHeader
     * @param false  $unsafeFileUpload
     * @param bool   $throwError
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function query(
        $action,
        $params = [],
        $dataType = self::DATA_TYPE_JSON,
        $credentials = [],
        $credentialsIntoHeader = false,
        $unsafeFileUpload = false,
        $throwError = true
    ) {
        switch ($dataType) {
            case self::DATA_TYPE_URL:
                $data = http_build_query($params);
                $httpHeader = ['Content-Type: application/x-www-form-urlencoded; charset=utf-8'];
                break;
            case self::DATA_TYPE_MULTIPART:
                $data = $params;
                $httpHeader = ['Content-Type: multipart/form-data'];
                break;
            case self::DATA_TYPE_JSON:
            default:
                $data = json_encode($params);
                $httpHeader = ['Content-Type: application/json'];
                break;
        }

        if ($credentialsIntoHeader) {
            $httpHeader = array_merge($httpHeader, $credentials);
        }

        $url = $this->getApiUrl($action);

        $this->logger->debug(__METHOD__, ['url' => $url]);

        $ch = curl_init();
        curl_setopt_array(
            $ch,
            [
                CURLOPT_URL            => $url,
                CURLOPT_HTTPHEADER     => $httpHeader,
                CURLOPT_POST           => 1,
                CURLOPT_POSTFIELDS     => $data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_BINARYTRANSFER => 1,
            ]
        );

        if ($unsafeFileUpload) {
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        }

        $response = curl_exec($ch);
        if (!$response) {
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            $this->logger->error(
                __METHOD__,
                [
                    'curl_errno' => $curlErrno,
                    'curl_error' => $curlError,
                ]
            );
            curl_close($ch);
            throw new \Exception($curlError, $curlErrno);
        } else {
            $this->lastResponse = $response;
        }

        $returnStatus = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return $this->parseResponse($returnStatus, $response, $throwError);
    }

    /**
     * @param int    $returnStatus
     * @param string $response
     * @param bool   $throwError
     *
     * @return array|mixed
     * @throws \Exception
     */
    protected function parseResponse($returnStatus, $response, $throwError)
    {
        preg_match('/--(.*)\b/', $response, $boundary);

        $content = empty($boundary)
            ? $this->parseMonoPartBody($response)
            : $this->parseMultiPartBody($response, $boundary[0]);

        switch ($returnStatus) {
            case 200:
                return $content;

            default:
                $this->logger->warning(
                    __METHOD__,
                    [
                        'returnStatus' => $returnStatus,
                        'jsonInfos'    => !empty($content['<jsonInfos>']) ? $content['<jsonInfos>'] : $content,
                    ]
                );

                if (!empty($content['<jsonInfos>'])) {
                    $content = $content['<jsonInfos>'];
                }

                if (isset($content['messages'])) {
                    $message = $content['messages'][0]['id'] . ' : ' . $content['messages'][0]['messageContent'];
                } elseif (!empty($content['error'])) {
                    $message = $content['error'];
                    if (!empty($content['message'])) {
                        $message .= ' : ' . $content['message'];
                    }
                } elseif (!empty($content['errorCode']) && !empty($content['errorLabel'])) {
                    $message = $content['errorCode'] . ': ' . $content['errorLabel'];
                } else {
                    $message = __('Unknown error');
                }

                if ($throwError) {
                    throw new \Exception('CURL error: ' . '(' . $returnStatus . ') ' . $message, $returnStatus);
                }

                return $content;
        }
    }

    protected function parseMultiPartBody($body, $boundary)
    {
        $messages = array_filter(
            array_map(
                'trim',
                explode($boundary, $body)
            )
        );

        $parts = [];
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

            if ('application/json' === $headers['content-type']) {
                $body = json_decode($body, true);
            }

            $parts[$headers['content-id']] = '<jsonInfos>' === $headers['content-id']
                ? json_decode($body, true)
                : $body;
        }

        return $parts;
    }

    protected function parseMonoPartBody($body)
    {
        return json_decode($body, true);
    }
}
