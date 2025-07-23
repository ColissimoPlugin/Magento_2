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

use LaPoste\Colissimo\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class DownloadButton extends Field
{
    /**
     * @var string
     */
    protected $_template = 'LaPoste_Colissimo::system/config/field/download.phtml';

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @param Context $context
     * @param Data    $helperData
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Data $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helperData = $helperData;
    }

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setData('field_id', $element->getId());

        return $this->_toHtml();
    }

    /**
     * Return url for downloading the log file
     *
     * @return string
     */
    public function getDownloadFileUrl(string $type)
    {
        return $this->getUrl(
            $this->helperData->getAdminRoute('configuration', 'downloadButton'),
            [
                'type' => $type,
            ]
        );
    }

    /**
     * Generate button html
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getButtonHtml()
    {
        if ('lpc_advanced_lpc_debug_downloadLogs' === $this->getData('field_id')) {
            $id = 'downloadLog_button';
            $downloadUrl = $this->getDownloadFileUrl('logs');
        } else {
            $id = 'downloadDoc_button';
            $downloadUrl = $this->getDownloadFileUrl('doc');
        }

        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id'      => $id,
                'label'   => __('Download'),
                'onclick' => 'window.open(\'' . $downloadUrl . '\');',
            ]
        );

        return $button->toHtml();
    }
}
