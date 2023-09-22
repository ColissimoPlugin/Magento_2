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

class BordereauAssociatedOptions implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'associated'   => ['label' => __('Only in a bordereau'), 'value' => 'associated'],
            'unassociated' => ['label' => __('Only *not* in a bordereau'), 'value' => 'unassociated'],
        ];
    }
}
