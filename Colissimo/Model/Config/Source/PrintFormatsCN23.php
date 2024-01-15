<?php
/**
 * Source Model for CN23 print format configuration field
 **/

namespace LaPoste\Colissimo\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PrintFormatsCN23 implements ArrayInterface
{

    /*
      * Option getter
      * @return array
    */
    public function toOptionArray()
    {
        $options = [
            ['value' => 'PDF_A4_300dpi', 'label' => __('PDF office printing, dimension A4 and resolution of 300dpi')],
            ['value' => 'PDF_10x12_300dpi', 'label' => __('PDF office printing, dimension 10cm x 12cm, and resolution of 300dpi')],
        ];

        return $options;
    }
}
