<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Model\Config\Source;

class CustomsCategory implements \Magento\Framework\Option\ArrayInterface
{
    const GIFT = 1;
    const COMMERCIAL_SAMPLE = 2;
    const COMMERCIAL_SHIPMENT = 3;
    const DOCUMENT = 4;
    const OTHER = 5;
    const RETURN_OF_ARTICLES = 6;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => null, 'label' => __('Other')],
            ['value' => self::GIFT, 'label' => __('Gift')],
            ['value' => self::COMMERCIAL_SAMPLE, 'label' => __('Commercial sample')],
            ['value' => self::COMMERCIAL_SHIPMENT, 'label' => __('Commercial shipment')],
            ['value' => self::DOCUMENT, 'label' => __('Document')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            null                      => __('Other'),
            self::GIFT                => __('Gift'),
            self::COMMERCIAL_SAMPLE   => __('Commercial sample'),
            self::COMMERCIAL_SHIPMENT => __('Commercial shipment'),
            self::DOCUMENT            => __('Document'),
        ];
    }
}
