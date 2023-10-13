<?php

namespace LaPoste\Colissimo\Api\Carrier;


interface OffersApi
{
    public function getColissimoOffers($addressee, $depositDate, $login = null, $password = null);
}
