<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session;
use \LaPoste\Colissimo\Logger;


class SetRelayInformationOrder implements ObserverInterface
{

    protected $_checkoutSession;
    protected $colissimoLogger;

    public function __construct(Session $checkoutSession, Logger\Colissimo $logger)
    {
        $this->_checkoutSession = $checkoutSession;
        $this->colissimoLogger = $logger;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if ($order) {
            $shippingAddress = $order->getShippingAddress();
            $shippingMethod = $order->getShippingMethod();
            $relayInformation = $this->_checkoutSession->getLpcRelayInformation();
            $this->_checkoutSession->setLpcRelayInformation([]);

            if ($shippingMethod == 'colissimo_pr') {
                if (!empty($relayInformation) && array_search("", $relayInformation) === false) {
                    $order->setLpcRelayId($relayInformation['id']);
                    $order->setLpcRelayType($relayInformation['type']);
                    $shippingAddress->setCompany($relayInformation['name']);
                    $shippingAddress->setStreet($relayInformation['address']);
                    $shippingAddress->setPostCode($relayInformation['post_code']);
                    $shippingAddress->setCity($relayInformation['city']);
                    $shippingAddress->setCountryId($relayInformation['country']);
                } else {
                    $this->colissimoLogger->error(__METHOD__, [__('Error LPC : Can\'t set relay information in order because at least one information is missing in session')]);
                }
            }
        } else {
            $this->colissimoLogger->error(__METHOD__, [__('Error LPC : Can\'t set relay information in order because can\'t access to order')]);
        }
    }
}
