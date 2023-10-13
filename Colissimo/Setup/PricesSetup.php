<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace LaPoste\Colissimo\Setup;

use Magento\Eav\Setup\EavSetup;

class PricesSetup extends EavSetup
{

    public function getDefaultEntities()
    {
        return [
            \LaPoste\Colissimo\Model\Prices::ENTITY => [
                'entity_model' => \LaPoste\Colissimo\Model\ResourceModel\Prices::class,
                'table'        => 'laposte_prices_entity',
                'attributes'   => [
                    'method'     => [
                        'type' => 'static',
                    ],
                    'area'       => [
                        'type' => 'static',
                    ],
                    'weight_min' => [
                        'type' => 'static',
                    ],
                    'weight_max' => [
                        'type' => 'static',
                    ],
                    'price_min'  => [
                        'type' => 'static',
                    ],
                    'price_max'  => [
                        'type' => 'static',
                    ],
                    'price'      => [
                        'type' => 'static',
                    ],
                ],
            ],
        ];
    }
}

