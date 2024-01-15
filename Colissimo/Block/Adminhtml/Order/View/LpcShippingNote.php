<?php

namespace LaPoste\Colissimo\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Backend\Block\Template;

class LpcShippingNote extends Template
{
    protected $orderRepository;

    public function __construct(Context $context, OrderRepositoryInterface $orderRepository, $data = [])
    {
        $this->orderRepository = $orderRepository;

        parent::__construct($context, $data);
    }

    public function lpcGetShippingNote()
    {
        $orderId = (int) $this->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($orderId);

        return $order->getLpcShippingNote();
    }
}
