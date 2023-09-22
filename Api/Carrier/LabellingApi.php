<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Api\Carrier;

interface LabellingApi
{
    /**
     * Calls the generateLabel service.
     *
     * @param $payload \LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload
     *
     * @return array [shipmentDataInfo, binary]
     */
    public function generateLabel(\LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload $payload);
}
