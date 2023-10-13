<?php
/**
 * Source Model for Colissimo Flash J+1 max hour selection
 **/

namespace LaPoste\Colissimo\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class FlashHours implements ArrayInterface
{

    /*
      * Option getter
      * @return array
    */
    public function toOptionArray()
    {
        $options = [
            ['value' => '00:00', 'label' => '00h'],
            ['value' => '00:30', 'label' => '00h30'],
            ['value' => '01:00', 'label' => '01h'],
            ['value' => '01:30', 'label' => '01h30'],
            ['value' => '02:00', 'label' => '02h'],
            ['value' => '02:30', 'label' => '02h30'],
            ['value' => '03:00', 'label' => '03h'],
            ['value' => '03:30', 'label' => '03h30'],
            ['value' => '04:00', 'label' => '04h'],
            ['value' => '04:30', 'label' => '04h30'],
            ['value' => '05:00', 'label' => '05h'],
            ['value' => '05:30', 'label' => '05h30'],
            ['value' => '06:00', 'label' => '06h'],
            ['value' => '06:30', 'label' => '06h30'],
            ['value' => '07:00', 'label' => '07h'],
            ['value' => '07:30', 'label' => '07h30'],
            ['value' => '08:00', 'label' => '08h'],
            ['value' => '08:30', 'label' => '08h30'],
            ['value' => '09:00', 'label' => '09h'],
            ['value' => '09:30', 'label' => '09h30'],
            ['value' => '10:00', 'label' => '10h'],
            ['value' => '10:30', 'label' => '10h30'],
            ['value' => '11:00', 'label' => '11h'],
            ['value' => '11:30', 'label' => '11h30'],
            ['value' => '12:00', 'label' => '12h'],
            ['value' => '12:30', 'label' => '12h30'],
            ['value' => '13:00', 'label' => '13h'],
            ['value' => '13:30', 'label' => '13h30'],
            ['value' => '14:00', 'label' => '14h'],
            ['value' => '14:30', 'label' => '14h30'],
            ['value' => '15:00', 'label' => '15h'],
            ['value' => '15:30', 'label' => '15h30'],
            ['value' => '16:00', 'label' => '16h'],
            ['value' => '16:30', 'label' => '16h30'],
            ['value' => '17:00', 'label' => '17h'],
            ['value' => '17:30', 'label' => '17h30'],
            ['value' => '18:00', 'label' => '18h'],
            ['value' => '18:30', 'label' => '18h30'],
            ['value' => '19:00', 'label' => '19h'],
            ['value' => '19:30', 'label' => '19h30'],
            ['value' => '20:00', 'label' => '20h'],
            ['value' => '20:30', 'label' => '20h30'],
            ['value' => '21:00', 'label' => '21h'],
            ['value' => '21:30', 'label' => '21h30'],
            ['value' => '22:00', 'label' => '22h'],
            ['value' => '22:30', 'label' => '22h30'],
            ['value' => '23:00', 'label' => '23h'],
            ['value' => '23:30', 'label' => '23h30'],
        ];

        return $options;
    }
}
