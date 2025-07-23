<?php

namespace LaPoste\Colissimo\Api\RelaysWebservice;

interface GenerateRelaysPayload
{
    /**
     * @param $weight
     * @return mixed
     */
    public function withWeight($weight);

    /**
     * @param array $address
     * @return mixed
     */
    public function withAddress(array $address);

    /**
     * @return mixed
     */
    public function withCredentials();

    /**
     * @param \DateTime|null $shippingDate
     * @return mixed
     */
    public function withShippingDate(?\DateTime $shippingDate = null);

    /**
     * @return mixed
     */
    public function withOptionInter();

    /**
     * @param $weight
     * @param $storeId
     * @return mixed
     */
    public function withRelayTypeFilter($weight, $storeId);

    /**
     * @return mixed
     */
    public function assemble();
}
