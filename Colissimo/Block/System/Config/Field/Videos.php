<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Block\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Videos extends Field
{
    /**
     * @var string
     */
    protected $_template = 'LaPoste_Colissimo::system/config/field/videos.phtml';

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

    public function getVideos(): array
    {
        return [
            'npyB9KYgWJk' => __('General presentation of the module'),
            'uIAv41ASFK8' => __('Shipping methods configuration'),
            'ey43rLrWPM0' => __('Labels generation'),
            'kTBdm_bHwx0' => __('Order with Colissimo pickup point'),
            'Zk_3Ow8HrFU' => __('Orders with customs declaration'),
            '1__ksj3hzLI' => __('Multi-parcels shipment'),
            'MLSEsosiBJk' => __('Multi-parcels shipment for overseas'),
            'Ud48ZbWWDHs' => __('Tracking parcels'),
            'Aqtyubxztxc' => __('Delivery Duty Paid (DDP)'),
            'WWhFyU71PSw' => __('Customs documents'),
            'ONJVPDA01ls' => __('Delivery slip'),
            'JiEVjHuV3R4' => __('Parcels returnal'),
        ];
    }
}
