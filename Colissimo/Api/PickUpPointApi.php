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
     * @param $login    if null, retrieve it from the configuration
     * @param $password if null, retrieve it from the configuration
     *
     * Will throw \LaPoste\Colissimo\Exception\ApiException if credential are not OK.
     */
    public function authenticate($login = null, $password = null);
}
