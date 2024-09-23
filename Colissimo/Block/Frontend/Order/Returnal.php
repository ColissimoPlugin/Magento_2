<?php

namespace LaPoste\Colissimo\Block\Frontend\Order;

use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\Carrier\Colissimo;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Block\Order\Link;

class Returnal extends Link
{
    const XML_PATH_AVAILABLE_TO_CUSTOMER = 'lpc_return_labels/availableToCustomer';
    const XML_PATH_AVAILABLE_TO_CUSTOMER_DAYS = 'lpc_return_labels/availableToCustomerDays';

    protected $helper;
    private $orderRepository;

    public function __construct(
        Context                  $context,
        DefaultPathInterface     $defaultPath,
        Registry                 $registry,
        OrderRepositoryInterface $orderRepository,
        Data                     $helper,
        array                    $data = []
    ) {
        parent::__construct($context, $defaultPath, $registry, $data);

        $this->helper = $helper;
        $this->orderRepository = $orderRepository;
    }

    protected function _toHtml()
    {
        // The feature must be enabled
        if (!$this->helper->getAdvancedConfigValue(self::XML_PATH_AVAILABLE_TO_CUSTOMER)) {
            return '';
        }

        $order = $this->getOrder();

        // The order must have a shipment
        if (empty($order) || !$order->hasShipments()) {
            return '';
        }

        // The order must be shipped with Colissimo
        $shippingMethod = $order->getShippingMethod();
        if (!str_starts_with($shippingMethod, Colissimo::CODE.'_')) {
            return '';
        }

        $daysAvailable = (int)$this->helper->getAdvancedConfigValue(self::XML_PATH_AVAILABLE_TO_CUSTOMER_DAYS);
        if (!empty($daysAvailable) && $order->getCreatedAt() < strtotime('-'.$daysAvailable.' days')) {
            return '';
        }

        // A Colissimo shipping label must exist for at least one shipment
        $shipments = $order->getShipmentsCollection();
        foreach ($shipments as $shipment) {
            $label = $shipment->getShippingLabel();
            if ($label) {
                return parent::_toHtml();
            }
        }

        return '';
    }

    public function getOrder()
    {
        $params = $this->getRequest()->getParams();
        if (empty($params['order_id'])) {
            return null;
        }

        return $this->orderRepository->get($params['order_id']);
    }
}
