<?php

namespace LaPoste\Colissimo\Block\Frontend\Order;

use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\Carrier\Colissimo;
use LaPoste\Colissimo\Model\CheckoutApi;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class DeliveryDate extends Template
{
    const XML_PATH_DISPLAY_DELIVERY_DATE = 'lpc_checkout/displayDeliveryDate';

    private OrderRepositoryInterface $orderRepository;
    protected CheckoutApi $checkoutApi;
    protected Data $helper;

    public function __construct(
        Context                  $context,
        OrderRepositoryInterface $orderRepository,
        CheckoutApi              $checkoutApi,
        Data                     $helper,
        array                    $data = []
    ) {
        parent::__construct($context, $data);

        $this->orderRepository = $orderRepository;
        $this->checkoutApi = $checkoutApi;
        $this->helper = $helper;
    }

    /**
     * Estimate the delivery date for the current order.
     *
     * The date is fetched live from the Colissimo API each time the page is
     * displayed and is never persisted.
     */
    public function getDeliveryDate(): ?string
    {
        // The delivery date feature must be enabled
        if (!$this->helper->getAdvancedConfigValue(self::XML_PATH_DISPLAY_DELIVERY_DATE)) {
            return null;
        }

        $order = $this->getOrder();
        if (empty($order)) {
            return null;
        }

        return $this->buildEstimate($order);
    }

    /**
     * Build the estimate for a given order, provided it is shipped with Colissimo.
     */
    protected function buildEstimate(OrderInterface $order): ?string
    {
        // The order must be shipped with Colissimo
        if (!str_starts_with((string) $order->getShippingMethod(), Colissimo::CODE . '_')) {
            return null;
        }

        $shippingAddress = $order->getShippingAddress();
        if (empty($shippingAddress)) {
            return null;
        }

        $postcode = (string) $shippingAddress->getPostcode();
        if ('' === $postcode) {
            return null;
        }

        $depositDate = $this->resolveDepositDate($order);
        if ($this->requiresDepositDate() && null === $depositDate) {
            return null;
        }

        return $this->checkoutApi->getDeliveryDatePlain($postcode, $depositDate);
    }

    protected function resolveDepositDate(OrderInterface $order): ?string
    {
        return $this->getLabelGenerationDate($order);
    }

    protected function requiresDepositDate(): bool
    {
        return true;
    }

    protected function getLabelGenerationDate(OrderInterface $order): ?string
    {
        if (!$order->hasShipments()) {
            return null;
        }

        foreach ($order->getShipmentsCollection() as $shipment) {
            if (empty($shipment->getShippingLabel())) {
                continue;
            }

            $createdAt = $shipment->getCreatedAt();
            if (empty($createdAt)) {
                continue;
            }

            return (new \DateTime($createdAt))->format('Y-m-d');
        }

        return null;
    }

    protected function getOrder(): ?OrderInterface
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if (empty($orderId)) {
            return null;
        }

        try {
            return $this->orderRepository->get($orderId);
        } catch (\Exception $e) {
            return null;
        }
    }
}
