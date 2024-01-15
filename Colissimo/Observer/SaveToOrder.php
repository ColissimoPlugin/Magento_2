<?php

namespace LaPoste\Colissimo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SaveToOrder implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $quote = $event->getQuote();
        $order = $event->getOrder();
        $order->setData('lpc_shipping_note', $quote->getData('lpc_shipping_note'));
    }
}
