<?php
/**
 * Source Model for "point retrait" display font configuration field
 **/

namespace LaPoste\Colissimo\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PRDisplayFont implements ArrayInterface
{
    /*
      * Option getter
      * @return array
    */
    public function toOptionArray()
    {
        $options = [
            ['value' => 'georgia', 'label' => 'Georgia, serif'],
            ['value' => 'palatino', 'label' => '"Palatino Linotype", "Book Antiqua", Palatino, serif'],
            ['value' => 'times', 'label' => '"Times New Roman", Times, serif'],
            ['value' => 'arial', 'label' => 'Arial, Helvetica, sans-serif'],
            ['value' => 'arialblack', 'label' => '"Arial Black", Gadget, sans-serif'],
            ['value' => 'comic', 'label' => '"Comic Sans MS", cursive, sans-serif'],
            ['value' => 'impact', 'label' => 'Impact, Charcoal, sans-serif'],
            ['value' => 'lucida', 'label' => '"Lucida Sans Unicode", "Lucida Grande", sans-serif'],
            ['value' => 'tahoma', 'label' => 'Tahoma, Geneva, sans-serif'],
            ['value' => 'trebuchet', 'label' => '"Trebuchet MS", Helvetica, sans-serif'],
            ['value' => 'verdana', 'label' => 'Verdana, Geneva, sans-serif'],
            ['value' => 'courier', 'label' => '"Courier New", Courier, monospace'],
            ['value' => 'lucidaconsole', 'label' => '"Lucida Console", Monaco, monospace'],
        ];

        usort(
            $options,
            function ($a, $b) {
                return strcmp(ltrim($a['label'], '"'), ltrim($b['label'], '"'));
            }
        );

        return $options;
    }
}
