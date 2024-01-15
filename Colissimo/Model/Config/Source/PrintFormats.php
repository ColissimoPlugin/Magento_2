<?php
/**
 * Source Model for Print format configuration fields
 **/

namespace LaPoste\Colissimo\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PrintFormats implements ArrayInterface
{

    /*
      * Option getter
      * @return array
    */
    public function toOptionArray()
    {
        $options = [
            ['value' => 'PDF_A4_300dpi', 'label' => __('PDF office printing, dimension A4 and resolution of 300dpi')],
            ['value' => 'PDF_10x15_300dpi', 'label' => __('PDF office printing, dimension 10cm x 15cm, and resolution of 300dpi')],
            //['value' => 'PDF_10x12_300dpi', 'label' => __('PDF office printing, dimension 10cm x 12cm, and resolution of 300dpi')],
            //['value' => 'PDF_10x10_300dpi', 'label' => __('PDF office printing, dimension 10cm x 10cm, and resolution of 300dpi')],
        ];

        return $options;
    }
}
