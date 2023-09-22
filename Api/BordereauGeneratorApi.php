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

interface BordereauGeneratorApi
{
    public function generateBordereauByParcelsNumbers(array $parcelNumbers, $login = null, $password = null);

    public function getBordereauByNumber($bordereauNumber, $login = null, $password = null);
}
