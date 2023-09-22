<?php
/**
 * Created by PhpStorm.
 * User: nmekharbeche
 * Date: 19/12/2018
 * Time: 09:19
 */

namespace LaPoste\Colissimo\Block\Adminhtml\Shipment\Label;


use Magento\Framework\View\Element\Template;

class AbstractLabel extends Template
{
    protected $_template = 'LaPoste_Colissimo::shipment/label.phtml';
}