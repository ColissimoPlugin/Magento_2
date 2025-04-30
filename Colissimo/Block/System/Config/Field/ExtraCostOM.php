<?php

namespace LaPoste\Colissimo\Block\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ExtraCostOM extends Field
{
    /**
     * Return element html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setData('placeholder', '18');

        return parent::_getElementHtml($element);
    }
}
