<?php

namespace LaPoste\Colissimo\Api\RelaysWebservice;

interface RelaysApi
{
    /**
     * Call the getRelays Service
     * @param $params
     * @return mixed
     */
    public function getRelays($params);
}