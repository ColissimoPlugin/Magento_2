<?php

namespace LaPoste\Colissimo\Api;

interface CotationApi
{
    /**
     * Compute the shipping price of a parcel according to the given criteria.
     *
     * @param array $params see \LaPoste\Colissimo\Model\CotationApi::calculateCost for the accepted keys
     *
     * @return array the raw decoded response of the "calculerTarif" web service
     * @throws \Exception
     */
    public function calculateCost(array $params): array;
}
