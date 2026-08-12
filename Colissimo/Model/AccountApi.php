<?php

namespace LaPoste\Colissimo\Model;

use LaPoste\Colissimo\Model\Config\Source\HazmatCategories;

class AccountApi extends RestApi implements \LaPoste\Colissimo\Api\AccountApi
{
    const API_BASE_URL = 'https://ws.colissimo.fr/api-ewe/v1/rest/';
    const CONTRACT_TYPE_FACILITE = 'FACILITE';
    const PROVIDER_OLD_ACCOUNT = 'COLISSIMO-V1';
    const PROVIDER_NEW_ACCOUNT = 'COLISSIMO-V2';

    const MARKER_PROVIDER = 'accountProvider';
    const MARKER_PROVIDER_CREDENTIALS = 'accountProviderCredentials';

    const CREDENTIAL_FIELDS = [
        'lpc_general/connectionMode',
        'lpc_general/id_webservices',
        'lpc_general/pwd_webservices',
        'lpc_general/api_key',
        'lpc_general/account_id',
        'lpc_general/parent_id_webservices',
    ];

    private $accountProvider;

    protected function getApiUrl($action)
    {
        return self::API_BASE_URL . $action;
    }

    /**
     * Must be set to API calls for both advanced users and new Colissimo accounts
     */
    public function getParentAccountId($storeId = null): string
    {
        $parentAccountId = (string) $this->helperData->getAdvancedConfigValue('lpc_general/parent_id_webservices', $storeId);

        // Some users enter their email address in here for some reason
        if (strpos($parentAccountId, '@') !== false) {
            $parentAccountId = '';
        }

        if (empty($parentAccountId) && $this->isNewAccount()) {
            $parentAccountId = $this->getOwnAccountId($storeId);
        }

        return $parentAccountId;
    }

    public function isNewAccount(): bool
    {
        return self::PROVIDER_NEW_ACCOUNT === $this->getAccountProvider();
    }

    public function getAccountProvider(): string
    {
        $provider = $this->readStoredAccountProvider();
        if ('' !== $provider) {
            return $provider;
        }

        // Records the provider as a side effect, even on the stripped payload
        $this->getAccountInformation();

        if (null === $this->accountProvider) {
            $this->accountProvider = '';
        }

        return $this->accountProvider;
    }

    public function resetAccountProvider(): void
    {
        $this->accountProvider = null;
        $this->helperData->setMarker(self::MARKER_PROVIDER, '');
        $this->helperData->setMarker(self::MARKER_PROVIDER_CREDENTIALS, '');
    }

    private function readStoredAccountProvider(): string
    {
        if (null !== $this->accountProvider) {
            return $this->accountProvider;
        }

        $markers = $this->helperData->getMarkers();
        if (empty($markers[self::MARKER_PROVIDER])) {
            return '';
        }

        if (($markers[self::MARKER_PROVIDER_CREDENTIALS] ?? '') !== $this->getCredentialsFingerprint()) {
            return '';
        }

        $this->accountProvider = (string) $markers[self::MARKER_PROVIDER];

        return $this->accountProvider;
    }

    private function storeAccountProvider(string $provider): void
    {
        $this->accountProvider = $provider;
        $this->helperData->setMarker(self::MARKER_PROVIDER, $provider);
        $this->helperData->setMarker(self::MARKER_PROVIDER_CREDENTIALS, $this->getCredentialsFingerprint());
    }

    private function getCredentialsFingerprint(): string
    {
        $values = [];
        foreach (self::CREDENTIAL_FIELDS as $field) {
            $values[] = (string) $this->helperData->getAdvancedConfigValue($field);
        }

        return hash('sha256', implode("\0", $values));
    }

    private function getParentAccountIdWithoutLookup(): string
    {
        $parentAccountId = (string) $this->helperData->getAdvancedConfigValue('lpc_general/parent_id_webservices');

        // Some users enter their email address in here for some reason
        if (strpos($parentAccountId, '@') !== false) {
            $parentAccountId = '';
        }

        if (!empty($parentAccountId)) {
            return $parentAccountId;
        }

        return self::PROVIDER_NEW_ACCOUNT === $this->readStoredAccountProvider() ? $this->getOwnAccountId() : '';
    }

    private function getOwnAccountId($storeId = null): string
    {
        return 'login' !== $this->helperData->getAdvancedConfigValue('lpc_general/connectionMode', $storeId)
            ? (string) $this->helperData->getAdvancedConfigValue('lpc_general/account_id', $storeId)
            : (string) $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices', $storeId);
    }

    public function getAutologinURLs(): array
    {
        static $urls = null;
        if (!empty($urls)) {
            return $urls;
        }

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

        $urls = $response;

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
        if (empty($accountInformation['contractType'])) {
            return true;
        }

        if (self::CONTRACT_TYPE_FACILITE !== $accountInformation['contractType'] || !empty($accountInformation['cgv']['accepted'])) {
            $this->helperData->setMarker('contractType', $accountInformation['contractType']);
            $this->helperData->setMarker('acceptedCgv', !empty($accountInformation['cgv']['accepted']));

            return true;
        }

        return false;
    }

    public function getAccountInformation(bool $withTag = false)
    {
        static $accountInformation = null;
        if (!empty($accountInformation)) {
            return $accountInformation;
        }

        $parentAccountIdSent = $this->getParentAccountIdWithoutLookup();

        $response = $this->queryAdditionalInformations($withTag);
        if (false === $response) {
            return false;
        }

        if (!empty($response['provider'])) {
            $this->storeAccountProvider((string) $response['provider']);
        }

        // Knowing the provider may have unlocked an account id worth resending
        $parentAccountId = $this->getParentAccountIdWithoutLookup();
        if (
            !$this->isCompleteAccountInformation($response)
            && '' !== $parentAccountId
            && $parentAccountId !== $parentAccountIdSent
        ) {
            $completeResponse = $this->queryAdditionalInformations($withTag);
            if (false !== $completeResponse) {
                $response = $completeResponse;
            }
        }

        if (empty($response['cgv']) || !$this->isCompleteAccountInformation($response)) {
            return false;
        }

        $accountInformation = $response;

        return $response;
    }

    private function isCompleteAccountInformation(array $response): bool
    {
        return [] !== array_diff(array_keys($response), ['cgv', 'provider']);
    }

    private function queryAdditionalInformations(bool $withTag)
    {
        $params = [];

        if ($withTag) {
            $params['tagInfoPartner'] = 'MAGENTO2';
        }

        try {
            $response = $this->query('additionalinformations', $params);

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

        return $response;
    }

    public function isHazmatOptionActive(): bool
    {
        $accountInformation = $this->getAccountInformation();

        return !empty($accountInformation['hazmatStatus']);
    }

    public function getHazmatCategories(): array
    {
        $accountInformation = $this->getAccountInformation();

        if (!$this->isHazmatOptionActive() || empty($accountInformation['hazmatCategories'])) {
            return [];
        }

        $hazmatCategories = HazmatCategories::HAZMAT_CATEGORIES;
        foreach ($hazmatCategories as $key => $hazmatCategory) {
            $hazmatCategories[$key]['active'] = in_array($hazmatCategory['code'], $accountInformation['hazmatCategories']);
        }

        return $hazmatCategories;
    }

    public function query(
        $action,
        $params = [],
        $dataType = self::DATA_TYPE_JSON,
        $credentials = [],
        $credentialsIntoHeader = false,
        $throwError = true
    ) {
        if ('api' === $this->helperData->getAdvancedConfigValue('lpc_general/connectionMode')) {
            $params['credential']['apiKey'] = $this->helperData->getAdvancedConfigValue('lpc_general/api_key');
        } else {
            $params['credential']['login'] = $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices');
            $params['credential']['password'] = $this->helperData->getAdvancedConfigValue('lpc_general/pwd_webservices');
        }

        $parentAccountId = $this->getParentAccountIdWithoutLookup();
        if (!empty($parentAccountId)) {
            $params['partnerClientCode'] = $parentAccountId;
        }

        return parent::query(
            $action,
            $params,
            $dataType,
            $credentials,
            $credentialsIntoHeader,
            $throwError
        );
    }
}
