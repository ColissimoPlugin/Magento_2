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
            ['value' => 'PDF_A4_300dpi', 'label' => __('PDF format A4 : impression bureautique en PDF, de dimension A4, et de résolution 300dpi')],
            ['value' => 'PDF_10x15_300dpi', 'label' => __('PDF format 10x15cm : impression bureautique en PDF, de dimension 10cm par 15cm, et de résolution 300dpi')],
        ];

        return $options;
    }
}
