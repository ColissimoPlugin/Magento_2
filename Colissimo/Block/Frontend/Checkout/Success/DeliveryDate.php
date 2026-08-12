<?php

namespace LaPoste\Colissimo\Block\Frontend\Checkout\Success;

use LaPoste\Colissimo\Block\Frontend\Order\DeliveryDate as OrderDeliveryDate;
use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\CheckoutApi;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class DeliveryDate extends OrderDeliveryDate
{
    const XML_PATH_DISPLAY_DELIVERY_DATE_CONFIRM = 'lpc_checkout/displayDeliveryDateOrderConfirm';

    private CheckoutSession $checkoutSession;

    public function __construct(
        Context                  $context,
        OrderRepositoryInterface $orderRepository,
        CheckoutApi              $checkoutApi,
        Data                     $helper,
        CheckoutSession          $checkoutSession,
        array                    $data = []
    ) {
        parent::__construct($context, $orderRepository, $checkoutApi, $helper, $data);

        $this->checkoutSession = $checkoutSession;
    }

    /**
     * The order has just been placed, so it is not shipped yet: base the estimate on the current
     * date (like the checkout) instead of on the label generation date.
     */
    protected function resolveDepositDate(OrderInterface $order): ?string
    {
        return null;
    }

    protected function requiresDepositDate(): bool
    {
        // When true and resolveDepositDate returns null, no date is shown on the page, which is what we want if the option is disabled
        if (!$this->helper->getAdvancedConfigValue(self::XML_PATH_DISPLAY_DELIVERY_DATE_CONFIRM)) {
            return true;
        }

        return false;
    }

    protected function getOrder(): ?OrderInterface
    {
        $order = $this->checkoutSession->getLastRealOrder();

        return $order && $order->getId() ? $order : null;
    }
}
