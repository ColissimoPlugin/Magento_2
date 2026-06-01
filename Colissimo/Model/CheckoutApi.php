<?php

namespace LaPoste\Colissimo\Model;

use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Logger\Colissimo;
use Magento\Framework\Escaper;

class CheckoutApi extends RestApi implements \LaPoste\Colissimo\Api\CheckoutApi
{
    const API_BASE_URL = 'https://ws.colissimo.fr/tunnel-commande/rest/TunnelCommandeWS/';
    const MAX_NB_TRIES_SCHEDULE = 14;
    const SECONDS_IN_A_DAY = 86400;

    protected $logger;
    protected $helperData;
    protected $escaper;

    public function __construct(
        Data $helperData,
        Colissimo $logger,
        Escaper $escaper
    ) {
        $this->helperData = $helperData;
        $this->logger = $logger;
        $this->escaper = $escaper;
    }

    protected function getApiUrl($action)
    {
        return self::API_BASE_URL . $action;
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
            $params['credentials']['apiKey'] = $this->helperData->getAdvancedConfigValue('lpc_general/api_key');
        } else {
            $params['credentials']['login'] = $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices');
            $params['credentials']['password'] = $this->helperData->getAdvancedConfigValue('lpc_general/pwd_webservices');
        }

        $parentAccountId = $this->helperData->getAdvancedConfigValue('lpc_general/parent_id_webservices');
        if (!empty($parentAccountId)) {
            $params['credentials']['partnerClientCode'] = $parentAccountId;
        }

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

    public function getDeliveryDate(string $postCode): ?string
    {
        $payload['data']['zipCodeDest'] = $postCode;
        $payload['data']['regateDepart'] = $this->helperData->getAdvancedConfigValue('lpc_checkout/deliveryDateDepositLocation');
        $payload['data']['depositDate'] = $this->getDepositDate();

        if (empty($payload['data']['depositDate'])) {
            return null;
        }

        try {
            $response = $this->query('getDateLivraison', $payload);

            if (empty($response['errorCode']) || 'OK' !== $response['errorCode']) {
                $this->logger->error(
                    'Delivery date request failed',
                    [
                        'method' => __METHOD__,
                        'error'  => $response['message'] ?? ($response['errorCode'] ?? 'Unknown error'),
                    ]
                );

                return null;
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Delivery date request failed',
                [
                    'method' => __METHOD__,
                    'error'  => $e->getMessage(),
                ]
            );

            return null;
        }

        $this->logger->debug(
            'Getting delivery date',
            [
                'method' => __METHOD__,
                'response' => $response,
            ]
        );

        return !empty($response['deliveryDate']) ? $this->formatDeliveryDate($response['deliveryDate']) : null;
    }

    private function getDepositDate(): ?string
    {
        $cuttOffDates = $this->helperData->getAdvancedConfigValue('lpc_checkout/deliveryDateCuttoffTimes');
        if (empty($cuttOffDates)) {
            return null;
        }

        $cuttOffDates = json_decode($cuttOffDates, true);
        if (empty($cuttOffDates['weekly_schedule'])) {
            return null;
        }

        $nbTries = 0;
        $time = time() - date('Z');
        $currentTime = (int) date('H', $time);

        // Find the processing day
        do {
            $processingTime = $time + ($nbTries * self::SECONDS_IN_A_DAY);
            $processingDate = date('Y-m-d', $processingTime);
            $processingWeekday = date('N', $processingTime);

            // Check exceptions first
            $cuttOffTimeFromRules = $this->getExceptionCuttOff($cuttOffDates, $processingDate);

            // Get global weekday rule as a fallback
            if (empty($cuttOffTimeFromRules)) {
                $cuttOffTimeFromRules = $cuttOffDates['weekly_schedule'][Data::DAYS[$processingWeekday]]['cuttOff'] ?? null;
            }

            // For the first day, we accept orders placed before the cuttoff hour. For next days the order is ready the first business hour so don't check the time
            if (0 === $nbTries && !empty($cuttOffTimeFromRules) && ('none' === $cuttOffTimeFromRules || $currentTime > (int) $cuttOffTimeFromRules)) {
                $cuttOffTimeFromRules = null;
            }

            $nbTries ++;
        } while ($nbTries < self::MAX_NB_TRIES_SCHEDULE && (empty($cuttOffTimeFromRules) || 'none' === $cuttOffTimeFromRules));

        if (empty($cuttOffTimeFromRules) || 'none' === $cuttOffTimeFromRules) {
            return null;
        }

        // Apply the processing time
        $preparationTime = (int) $this->helperData->getAdvancedConfigValue('lpc_checkout/averagePreparationDelay');

        if (!empty($cuttOffDates['weekly_schedule'][Data::DAYS[$processingWeekday]]['delay'])) {
            $preparationTime = (int) $cuttOffDates['weekly_schedule'][Data::DAYS[$processingWeekday]]['delay'];
        }

        $processingTime += $preparationTime * self::SECONDS_IN_A_DAY;

        return date('Y-m-d', $processingTime);
    }

    private function getExceptionCuttOff(array $cuttOffDates, string $date): ?string
    {
        if (empty($cuttOffDates['exceptions'])) {
            return null;
        }

        foreach ($cuttOffDates['exceptions'] as $oneException) {
            if ($oneException['date'] === $date) {
                return $oneException['hour'];
            }
        }

        return null;
    }

    private function formatDeliveryDate(string $deliveryDate): ?string
    {
        $dateTime = \DateTime::createFromFormat('d/m/Y', $deliveryDate);
        if (!$dateTime) {
            return null;
        }

        $text = $this->helperData->getAdvancedConfigValue('lpc_checkout/deliveryDateText');
        if (empty($text) || strpos($text, '{date}') === false) {
            $text = __('Delivery expected on {date}');
        }

        $format = $this->helperData->getAdvancedConfigValue('lpc_checkout/deliveryDateFormat');
        if (empty($format)) {
            $format = 'full';
        }

        switch ($format) {
            case 'full':
                $dateFormat = __('l, F j');
                break;
            case 'simple':
                $dateFormat = __('F j');
                break;
            case 'short':
                $dateFormat = __('M j');
                break;
            default:
                $dateFormat = $format;
        }

        $timestamp = $dateTime->getTimestamp();
        $date = $this->helperData->translateDate(date($dateFormat, $timestamp));

        $styles = '';
        $textColor = $this->helperData->getAdvancedConfigValue('lpc_checkout/deliveryDateColor');
        if (!empty($textColor)) {
            $styles .= 'color:' . $textColor . ';';
        }

        $textFont = $this->helperData->getFont('lpc_checkout/deliveryDateFont');
        if (!empty($textFont)) {
            $styles .= 'font-family:' . $textFont . ';';
        }

        $textSize = $this->helperData->getAdvancedConfigValue('lpc_checkout/deliveryDateSize');
        if (!empty($textSize) && 'default' !== $textSize) {
            $styles .= 'font-size:' . $textSize . ';';
        }

        return '<span style="' . $this->escaper->escapeHtmlAttr($styles) . '">' . $this->escaper->escapeHtml(str_replace('{date}', $date, $text)) . '</span>';
    }
}
