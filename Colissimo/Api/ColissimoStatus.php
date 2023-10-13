<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Api;

interface ColissimoStatus
{
    const ORDER_STATUS_DELIVERED = 'lpc_delivered';
    const ORDER_STATUS_ANOMALY = 'lpc_anomaly';
    const ORDER_STATUS_TRANSIT = 'lpc_transit';
    const ORDER_STATUS_READYTOSHIP = 'lpc_readyToShip';
    const ORDER_STATUS_PARTIAL_DELIVERY = 'lpc_partialDelivery';

    const UNKNOWN_STATUS_INTERNAL_CODE = - 1;

    public function getStatusInfo($intStatusCode);

    public function getInternalCodeForClp($clp);
}
