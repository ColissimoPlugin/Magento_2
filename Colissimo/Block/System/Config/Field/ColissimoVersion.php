<?php

/*******************************************************
 * Copyright (C) 2023 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Block\System\Config\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use LaPoste\Colissimo\Helper\Data;

class ColissimoVersion extends Field
{
    protected Data $helperData;

    public function __construct(
        Context $context,
        Data $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helperData = $helperData;
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $moduleVerison = $this->helperData->getModuleVersion();

        return '<p>' . $moduleVerison . '</p>';
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
