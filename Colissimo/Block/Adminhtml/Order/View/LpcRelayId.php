<?php

namespace LaPoste\Colissimo\Block\Adminhtml\Order\View;

use \Magento\Backend\Block\Template\Context;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \LaPoste\Colissimo\Model\Carrier\Colissimo;

class LpcRelayId extends \Magento\Backend\Block\Template
{

    protected $orderRepository;


    public function __construct(Context $context, OrderRepositoryInterface $orderRepository, array $data = [])
    {
        $this->orderRepository = $orderRepository;

        parent::__construct($context, $data);
    }

    public function lpcGetRelayInformation()
    {
        $order = $this->orderRepository->get($this->getOrderId());

        if ($this->isLpcRelayOrder($order)) {
            $relayInformation = [
                "relayName" => $order->getShippingAddress()->getCompany(),
                "relayId"   => $order->getLpcRelayId(),
                "relayType" => $order->getLpcRelayType(),
            ];

            return $relayInformation;
        } else {
            return false;
        }
    }

    private function getOrderId()
    {
        return (int) $this->getRequest()->getParam('order_id');
    }

    /**
     * @param $order
     * @return bool
     */
    private function isLpcRelayOrder($order)
    {
        return $order->getShippingMethod() == Colissimo::CODE . "_" . Colissimo::CODE_SHIPPING_METHOD_RELAY;
    }
}
