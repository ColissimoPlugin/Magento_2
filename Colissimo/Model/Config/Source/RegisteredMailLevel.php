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

use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

class RegisteredMailLevel implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => null, 'label' => __('(none)')],
            ['value' => 'R1', 'label' => __('R1')],
            ['value' => 'R2', 'label' => __('R2')],
            ['value' => 'R3', 'label' => __('R3')],
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
            null => __('(none)'),
            'R1' => __('R1'),
            'R2' => __('R2'),
            'R3' => __('R3'),
        ];
    }
}
