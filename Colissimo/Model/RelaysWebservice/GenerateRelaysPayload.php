<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Model\RelaysWebservice;

use \LaPoste\Colissimo\Helper\Data;

class GenerateRelaysPayload implements \LaPoste\Colissimo\Api\RelaysWebservice\GenerateRelaysPayload
{
    protected $payload;

    protected $helperData;

    public function __construct(Data $helperData)
    {
        $this->payload = [
            'origin' => 'CMS',
        ];
        $this->helperData = $helperData;
    }

    public function withCredentials()
    {
        $connectionMode = $this->helperData->getAdvancedConfigValue('lpc_general/connectionMode');
        if ('api' === $connectionMode) {
            $this->payload['apikey'] = $this->helperData->getAdvancedConfigValue('lpc_general/api_key');
        } else {
            $this->payload['accountNumber'] = $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices');
            $this->payload['password'] = $this->helperData->getAdvancedConfigValue('lpc_general/pwd_webservices');
        }

        $parentAccountId = $this->helperData->getAdvancedConfigValue('lpc_general/parent_id_webservices');
        if (!empty($parentAccountId)) {
            $this->payload['codTiersPourPartenaire'] = $parentAccountId;
        }

        return $this;
    }

    public function withAddress(array $address)
    {
        $this->payload['address'] = $address['address'];
        $this->payload['zipCode'] = $address['zipCode'];
        $this->payload['city'] = $address['city'];
        $this->payload['countryCode'] = $address['countryCode'];

        return $this;
    }

    public function withWeight($weight)
    {
        if (empty($weight)) {
            unset($this->payload['weight']);
        } else {
            $this->payload['weight'] = $weight;
        }

        return $this;
    }

    public function withShippingDate(\DateTime $shippingDate = null)
    {
        if (null === $shippingDate) {
            $shippingDate = new \DateTime();
            $numberOfDayPreparation = (int) $this->helperData->getAdvancedConfigValue('lpc_labels/averagePreparationDelay');
            $shippingDate->add(new \DateInterval('P' . $numberOfDayPreparation . 'D'));
        }

        if (empty($shippingDate)) {
            unset($this->payload['shippingDate']);
        } else {
            $this->payload['shippingDate'] = $shippingDate->format('d/m/Y');
        }

        return $this;
    }

    public function withOptionInter()
    {
        if ($this->payload['countryCode'] === 'FR') {
            $this->payload['optionInter'] = '0';
        } else {
            $this->payload['optionInter'] = '1';
        }

        return $this;
    }

    public function withRelayTypeFilter($weight, $storeId)
    {
        if ($weight > 20) {
            $this->payload['filterRelay'] = '0';

            return $this;
        }

        $this->payload['filterRelay'] = $this->helperData->getAdvancedConfigValue('lpc_pr_front/relayTypes', $storeId);

        return $this;
    }

    public function checkConsistency()
    {
        $this->checkLogin();
        $this->checkAddress();
        $this->checkOptions();
    }

    protected function checkLogin()
    {
        if ('api' === $this->helperData->getAdvancedConfigValue('lpc_general/connectionMode')) {
            if (empty($this->payload['apikey'])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('An API key is required to get relay points'));
            }
        } else {
            if (empty($this->payload['accountNumber']) || empty($this->payload['password'])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Login and password required to get relay points'));
            }
        }
    }

    protected function checkAddress()
    {
        if (empty($this->payload['zipCode'])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Zipcode required to get relay points'));
        }

        if (empty($this->payload['city'])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('City required to get relay points'));
        }

        if (empty($this->payload['countryCode'])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Country code required to get relay points'));
        }
    }

    protected function checkOptions()
    {
        if (empty($this->payload['shippingDate'])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Shipping date required to get relay points'));
        }

        if (!empty($this->payload['optionInter']) && $this->payload['optionInter'] == '1' && $this->payload['countryCode'] === 'FR') {
            throw new \Magento\Framework\Exception\LocalizedException(__('The international option can\'t be enable if the country destination is France'));
        }
    }

    public function assemble()
    {
        return array_merge($this->payload); // makes a copy
    }
}
