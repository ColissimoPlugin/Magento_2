<?php

namespace LaPoste\Colissimo\Model\Config\Source;

class PRDefaultMobileDisplay implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        $options = [
            ['value' => 'maplist', 'label' => 'Map and list'],
            ['value' => 'list', 'label' => 'List only'],
        ];

        return $options;
    }
}
