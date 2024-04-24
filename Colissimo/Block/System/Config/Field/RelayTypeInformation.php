<?php

namespace LaPoste\Colissimo\Block\System\Config\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;

class RelayTypeInformation extends Field
{

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return '<p>'.__('If cart weight is higher than 20 kg, only post office relays will be available.').'</p>';
    }

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }
}
