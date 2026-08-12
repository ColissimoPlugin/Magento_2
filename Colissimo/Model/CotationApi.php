<?php

namespace LaPoste\Colissimo\Model;

class CotationApi extends RestApi implements \LaPoste\Colissimo\Api\CotationApi
{
    const API_BASE_URL = 'https://ws.colissimo.fr/cotation-ws-cxf/rest/external/servicesTarification/';

    const BOOLEAN_FIELDS = [
        'avecSignature',
        'livraisonDomicile',
        'retour',
        'avecEngagement',
        'offreEntreprise',
        'engagementDelai',
        'economique',
    ];

    protected function getApiUrl($action)
    {
        return self::API_BASE_URL . $action;
    }

    /**
     * @inheritDoc
     */
    public function calculateCost(array $params): array
    {
        $payload = $this->prepareParams($params);

        $this->logger->debug(
            __METHOD__ . ' request',
            [
                'url' => $this->getApiUrl('calculerTarif'),
                'params' => $payload,
            ]
        );

        if ('api' === $this->helperData->getAdvancedConfigValue('lpc_general/connectionMode')) {
            $credentials = [
                'apiKey: ' . $this->helperData->getAdvancedConfigValue('lpc_general/api_key'),
            ];
        } else {
            $credentials = [
                'login: ' . $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices'),
                'password: ' . $this->helperData->getAdvancedConfigValue('lpc_general/pwd_webservices'),
            ];
        }

        try {
            // Do not throw on HTTP errors: the web service returns its own "errors" payload
            // (e.g. INVALID_POSTAL_CODE) that we want to forward to the front-end as is.
            $response = $this->query(
                'calculerTarif',
                $payload,
                self::DATA_TYPE_JSON,
                $credentials,
                true,
                false
            );
        } catch (\Exception $exception) {
            $this->logger->error(
                __METHOD__ . ' response',
                [
                    'error' => $exception->getMessage(),
                ]
            );

            return [];
        }

        if (!is_array($response)) {
            $response = [];
        }

        $this->logger->debug(
            __METHOD__ . ' response',
            [
                'response' => $response,
            ]
        );

        return $response;
    }

    /**
     * Normalize and cast the raw form values before sending them to the web service.
     *
     * @param array $params
     *
     * @return array
     */
    protected function prepareParams(array $params): array
    {
        $payload = [];

        $stringFields = [
            'codePaysExpediteur',
            'codePostalExpediteur',
            'codePaysDestinataire',
            'codePostalDestinataire',
            'typeSiteLivraison',
            'sousCompteClient',
        ];
        foreach ($stringFields as $field) {
            if (isset($params[$field]) && '' !== $params[$field]) {
                $payload[$field] = (string) $params[$field];
            }
        }

        foreach (self::BOOLEAN_FIELDS as $field) {
            if (isset($params[$field])) {
                $payload[$field] = filter_var($params[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }

        if (isset($params['poids']) && '' !== $params['poids']) {
            $payload['poids'] = (float) $params['poids'];
        }

        // 0 : general price, 1 : discounted price
        $payload['typeTarif'] = isset($params['typeTarif']) ? (int) $params['typeTarif'] : 0;

        if (!empty($params['optionsValorisees']) && is_array($params['optionsValorisees'])) {
            foreach ($params['optionsValorisees'] as $option) {
                if (empty($option['codeOption'])) {
                    continue;
                }

                $payload['optionsValorisees'][] = [
                    'codeOption' => (string) $option['codeOption'],
                    'choix'      => isset($option['choix']) ? (string) $option['choix'] : '',
                ];
            }
        }

        return $payload;
    }
}
