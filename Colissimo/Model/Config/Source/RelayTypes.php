<?php

namespace LaPoste\Colissimo\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class RelayTypes implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '0', 'label' => __('Only Post offices')],
            ['value' => '1', 'label' => __('All relays')],
            ['value' => '2', 'label' => __('Only Pickup points')],
            ['value' => '3', 'label' => __('Only Pickup partner stores')],
            ['value' => '5', 'label' => __('Only Post offices and Pickup partner stores')],
            ['value' => '10', 'label' => __('All relays without Pickup lockers')],
            ['value' => '11', 'label' => __('All relays without Neighbor-relays Pickme')],
        ];
    }
}
