<?php

namespace LaPoste\Colissimo\Observer;

use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Logger\Colissimo;
use LaPoste\Colissimo\Model\Carrier\Colissimo as ColissimoCarrier;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;

class SetRelayInformationOrder implements ObserverInterface
{
    protected Session $_checkoutSession;
    protected Colissimo $logger;
    protected Data $helperData;

    public function __construct(
        Session $checkoutSession,
        Colissimo $logger,
        Data $helperData
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->helperData = $helperData;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if (!$order) {
            $this->logger->error(__METHOD__, ['Error LPC: Can\'t set relay information in order because could not access order']);

            return;
        }

        $relayInformation = $this->getRelayInformation($order);
        $shippingMethod = $order->getShippingMethod();
        if ($shippingMethod !== ColissimoCarrier::CODE . '_' . ColissimoCarrier::CODE_SHIPPING_METHOD_RELAY) {
            if (!empty($relayInformation['name'])) {
                $shippingAddress = $order->getShippingAddress();
                if ($relayInformation['name'] === $shippingAddress->getCompany()) {
                    $shippingAddress->setCompany($order->getBillingAddress()->getCompany());
                }
            }

            return;
        }


        if (!empty($relayInformation) && !in_array('', $relayInformation)) {
            $order->setLpcRelayId($relayInformation['id']);
            $order->setLpcRelayType($relayInformation['type']);
            $shippingAddress = $order->getShippingAddress();
            $shippingAddress->setCompany($relayInformation['name']);
            $shippingAddress->setStreet($relayInformation['address']);
            $shippingAddress->setPostCode($relayInformation['post_code']);
            $shippingAddress->setCity($relayInformation['city']);
            $shippingAddress->setCountryId($relayInformation['country']);
        } else {
            $this->logger->error(
                __METHOD__,
                [
                    'Error LPC: Can\'t set relay information in order because at least one information is missing in session',
                    $order->getCustomerName(),
                    'Payment method: ' . $order->getPayment()->getMethodInstance()->getTitle(),
                    $relayInformation,
                ]
            );
        }
    }

    private function getRelayInformation($order): array
    {
        // Remote IP is null for orders created from the admin part
        if ($order->getRemoteIp() === null) {
            $markers = $this->helperData->getMarkers();

            if (empty($markers['admin_order_creation_relay'])) {
                $relayInformation = [];
            } else {
                $relayInformation = [
                    'id'        => $markers['admin_order_creation_relay']['id'],
                    'type'      => $markers['admin_order_creation_relay']['type'],
                    'name'      => $markers['admin_order_creation_relay']['name'],
                    'address'   => $markers['admin_order_creation_relay']['address'],
                    'post_code' => $markers['admin_order_creation_relay']['post_code'],
                    'city'      => $markers['admin_order_creation_relay']['city'],
                    'country'   => $markers['admin_order_creation_relay']['country'],
                ];

                $this->helperData->setMarker('admin_order_creation_relay', []);
            }
        } else {
            $relayInformation = $this->_checkoutSession->getLpcRelayInformation();
        }

        return $relayInformation ?? [];
    }
}
