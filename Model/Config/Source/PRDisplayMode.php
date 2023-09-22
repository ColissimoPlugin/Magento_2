<?php
/**
 * Source Model for "point retrait" display mode configuration field
 **/

namespace LaPoste\Colissimo\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PRDisplayMode implements ArrayInterface
{
    /*
      * Option getter
      * @return array
    */
    public function toOptionArray()
    {
        return [
            ['value' => 'widget', 'label' => __('Colissimo Widget')],
            ['value' => 'gmaps', 'label' => __('Google Maps (requires an API key)')],
            ['value' => 'leaflet', 'label' => __('Open Street Maps (free)')],
        ];
    }
}
