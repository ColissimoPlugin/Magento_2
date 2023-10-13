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
     * @param null $password
     * @return mixed
     */
    public function withPassword($password = null);

    /**
     * @param null $login
     * @return mixed
     */
    public function withLogin($login = null);

    /**
     * @param \DateTime|null $shippingDate
     * @return mixed
     */
    public function withShippingDate(\DateTime $shippingDate = null);

    /**
     * @param null $optionInter
     * @return mixed
     */
    public function withOptionInter($optionInter = null);

    /**
     * @return mixed
     */
    public function assemble();
}