<?php
/**
 * Source Model for Colissimo Flash J+1 max hour selection
 **/

namespace LaPoste\Colissimo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SendingService implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'dpd', 'label' => 'DPD'],
            ['value' => 'partner', 'label' => __('Local postal service')],
        ];
    }
}
