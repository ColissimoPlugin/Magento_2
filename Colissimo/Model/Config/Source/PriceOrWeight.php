<?php
/**
 * Source Model to choose between fees based on cart price or cart weight
 **/

namespace LaPoste\Colissimo\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PriceOrWeight implements ArrayInterface
{

    /*
      * Option getter
      * @return array
    */
    public function toOptionArray()
    {
        $options = [
            ['value' => 'cartweight', 'label' => __('Cart weight')],
            ['value' => 'cartprice', 'label' => __('Cart price')],
        ];

        return $options;
    }
}
