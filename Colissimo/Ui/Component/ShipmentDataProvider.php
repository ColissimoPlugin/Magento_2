<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Ui\Component;

class ShipmentDataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ('without_label' === $filter->getField()) {
            switch ($filter->getValue()) {
                case 'with':
                    $filter->setField('track_number');
                    $filter->setConditionType('notnull');
                    break;
                case 'without':
                    $filter->setField('track_number');
                    $filter->setConditionType('null');
                    break;
                default:
                    // do nothing
                    return;
            }
        }

        return parent::addFilter($filter);
    }
}
