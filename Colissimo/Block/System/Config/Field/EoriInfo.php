<?php

namespace LaPoste\Colissimo\Block\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class EoriInfo extends Field
{
    protected $_template = 'LaPoste_Colissimo::system/config/field/eoriInfo.phtml';

    /**
     * @param Context    $context
     * @param array      $data
     */
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    /**
     * Return element html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }
}
