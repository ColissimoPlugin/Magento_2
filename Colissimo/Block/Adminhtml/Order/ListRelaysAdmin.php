<?php

namespace LaPoste\Colissimo\Block\Adminhtml\Order;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ListRelaysAdmin extends Template
{
    protected $_template = 'LaPoste_Colissimo::order/list_relays_admin.phtml';
    private $listRelays;

    public function getListRelays()
    {
        return $this->listRelays;
    }

    public function setListRelays(array $newListRelays)
    {
        $this->listRelays = $newListRelays;
    }

    public function formatRelaysOpeningHours($hour)
    {
        return str_replace([' ', ' - 00:00-00:00'], [' - ', ''], $hour);
    }
}
