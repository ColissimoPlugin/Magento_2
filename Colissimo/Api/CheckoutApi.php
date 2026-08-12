<?php

namespace LaPoste\Colissimo\Api;

interface CheckoutApi
{
    public function getDeliveryDate(string $postCode): ?string;

    public function getDeliveryDatePlain(string $postCode, ?string $depositDate = null): ?string;
}
