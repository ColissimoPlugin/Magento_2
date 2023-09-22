<?php


namespace LaPoste\Colissimo\Ui\Component\Create\Form\Prices;

use Magento\Framework\Data\OptionSourceInterface;
use LaPoste\Colissimo\Model\Carrier\Colissimo;

class Options implements OptionSourceInterface
{
    public function toOptionArray()
    {
        $methods = Colissimo::METHODS_CODES_TRANSLATIONS;

        $options = [];
        foreach ($methods as $methodId => $methodText) {
            $options[] = [
                'value' => $methodId,
                'label' => __($methodText),
            ];
        }

        return $options;
    }
}
