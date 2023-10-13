<?php

namespace LaPoste\Colissimo\Model\Config\Source;

class ChooseRelayType implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => 'France',
                'value' => [
                    ['value' => 'A2P', 'label' => __('Pickup Station') . ' (A2P)'],
                    ['value' => 'BPR', 'label' => __('Post office') . ' (BPR)'],
                ],
            ],
            [
                'label' => 'International',
                'value' => [
                    ['value' => 'CMT', 'label' => __('Relay point') . ' (CMT)'],
                    ['value' => 'PCS', 'label' => __('Pickup Station') . ' (PCS)'],
                    ['value' => 'BDP', 'label' => __('Post office') . ' (BDP)'],
                ],
            ],

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
            'A2P' => __('Pickup Station') . ' (A2P)',
            'BPR' => __('Post office') . ' (BPR)',
            'CMT' => __('Relay point') . ' (CMT)',
            'PCS' => __('Pickup Station') . ' (PCS)',
            'BDP' => __('Post office') . ' (BDP)',
        ];
    }
}
