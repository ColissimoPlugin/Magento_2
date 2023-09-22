<?php


namespace LaPoste\Colissimo\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ListRelays extends Template
{
    protected $_template = "list_relays.phtml";

    private $listRelays;

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

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
        $formattedHours = str_replace([' ', ' - 00:00-00:00'], [' - ', ''], $hour);

        return $formattedHours;
    }
}