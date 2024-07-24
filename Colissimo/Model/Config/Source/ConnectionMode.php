<?php
/**
 * Source Model for connection methods to Colissimo APIs
 */

namespace LaPoste\Colissimo\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ConnectionMode implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'login', 'label' => __('Colissimo account')],
            ['value' => 'api', 'label' => __('Web services application key')],
        ];
    }
}
