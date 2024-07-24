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

interface PickUpPointApi
{
    /**
     * Authenticate against the Colissimo Api, storing the resulting token for next calls.
     *
     * Will throw \LaPoste\Colissimo\Exception\ApiException if credential are not OK.
     */
    public function authenticate();
}
