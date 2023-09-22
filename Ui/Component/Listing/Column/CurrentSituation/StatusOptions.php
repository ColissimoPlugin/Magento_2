<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Ui\Component\Listing\Column\CurrentSituation;

use Magento\Framework\Data\OptionSourceInterface;

class StatusOptions implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'anomaly'  => ['label' => __('Only in anomaly'), 'value' => 'anomaly'],
            'to_print' => ['label' => __('Only to print'), 'value' => 'to_print'],
        ];
    }
}
