<?php

namespace LaPoste\Colissimo\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Convert\Order;

class Shipment extends AbstractHelper
{
    protected Order $convertOrder;
    protected Data $helperData;

    /**
     * Shipment constructor.
     * @param Context $context
     * @param Order   $convertOrder
     * @param Data    $helperData
     */
    public function __construct(
        Context $context,
        Order $convertOrder,
        Data $helperData
    ) {
        parent::__construct($context);
        $this->convertOrder = $convertOrder;
        $this->helperData = $helperData;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return \Magento\Sales\Model\Order\Shipment
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createShipment(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $this->convertOrder->toShipment($order);

        foreach ($order->getAllItems() as $orderItem) {
            if (empty($orderItem->getQtyToShip()) || $orderItem->getIsVirtual()) {
                continue;
            }

            $qtyShipped = $orderItem->getQtyToShip();

            // Create shipment item with qty
            $shipmentItem = $this->convertOrder
                ->itemToShipmentItem($orderItem)
                ->setQty($qtyShipped);
            $shipment->addItem($shipmentItem);
        }

        // Register shipment
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);

        return $shipment;
    }

    public function shipmentToPackages(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $package = [
            'params' => [
                'weight'        => 0,
                'customs_value' => 0,
                'container'     => '',
                'length'        => '',
                'width'         => '',
                'height'        => '',
            ],
            'items'  => [],
        ];

        $order = $shipment->getOrder();

        $hsCodeAttribute = $this->helperData->getAdvancedConfigValue('lpc_labels/hsCodeAttribute', $order->getStoreId());
        if (empty($hsCodeAttribute)) {
            $hsCodeAttribute = 'lpc_hs_code';
        }

        foreach ($shipment->getAllItems() as $item) {
            $qtyToShip = $item->getQty();

            $package['params']['weight'] += $qtyToShip * $item->getWeight();
            $package['params']['customs_value'] += $qtyToShip * $item->getPrice();

            $orderItem = $item->getOrderItem();
            if (empty($order)) {
                $order = $orderItem->getOrder();
            }

            $package['items'][] = [
                'qty'                    => (int) $qtyToShip,
                'weight'                 => (int) $item->getWeight(),
                'customs_value'          => $item->getPrice(),
                'price'                  => $item->getPrice(),
                'name'                   => $item->getName(),
                'product_id'             => $item->getProductId(),
                'order_item_id'          => $item->getOrderItemId(),
                'currency'               => $order->getOrderCurrencyCode(),
                'sku'                    => $item->getSku(),
                'row_weight'             => $item->getWeight() * $qtyToShip,
                'country_of_manufacture' => $orderItem->getProduct()->getCountryOfManufacture(),
                'lpc_hs_code'            => $orderItem->getProduct()->getData($hsCodeAttribute),
            ];
        }

        return [$package];
    }

    // Used for partial return from the front
    public function partialShipmentToPackages(\Magento\Sales\Model\Order $order, array $productsToReturn)
    {
        $package = [
            'params' => [
                'weight'        => 0,
                'customs_value' => 0,
                'container'     => '',
                'length'        => '',
                'width'         => '',
                'height'        => '',
            ],
            'items'  => [],
        ];

        $hsCodeAttribute = $this->helperData->getAdvancedConfigValue('lpc_labels/hsCodeAttribute', $order->getStoreId());
        if (empty($hsCodeAttribute)) {
            $hsCodeAttribute = 'lpc_hs_code';
        }

        foreach ($order->getAllItems() as $item) {
            // Only take order products selected for return
            if (!array_key_exists($item['product_id'], $productsToReturn) || $productsToReturn[$item['product_id']] === 0) {
                continue;
            }

            $qtyShipped = $item->getQtyShipped();
            $qtyToReturn = $productsToReturn[$item['product_id']];
            if ($qtyToReturn > $qtyShipped) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Quantity to return exceeding quantity ordered'));
            }

            $package['params']['weight'] += $qtyToReturn * $item->getWeight();
            $package['params']['customs_value'] += $qtyToReturn * $item->getPrice();

            $package['items'][] = [
                'qty'                    => (int) $qtyToReturn,
                'weight'                 => (int) $item->getWeight(),
                'customs_value'          => $item->getPrice(),
                'price'                  => $item->getPrice(),
                'name'                   => $item->getName(),
                'product_id'             => $item->getProductId(),
                'order_item_id'          => $item->getOrderItemId(),
                'currency'               => $order->getOrderCurrencyCode(),
                'sku'                    => $item->getSku(),
                'row_weight'             => $item->getWeight() * $qtyToReturn,
                'country_of_manufacture' => $item->getProduct()->getCountryOfManufacture(),
                'lpc_hs_code'            => $item->getProduct()->getData($hsCodeAttribute),
            ];
        }

        return [$package];
    }
}
