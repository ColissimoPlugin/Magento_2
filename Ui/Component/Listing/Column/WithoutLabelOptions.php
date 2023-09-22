<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\Data\OptionSourceInterface;

class WithoutLabelOptions implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'with'    => ['label' => __('Only with label'), 'value' => 'with'],
            'without' => ['label' => __('Only without label'), 'value' => 'without'],
        ];
    }
}
