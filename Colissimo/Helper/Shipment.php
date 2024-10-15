<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Convert\Order;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Shipment extends AbstractHelper
{
    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    protected $convertOrder;

    protected $scopeInterface;
    /**
     * Shipment constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Sales\Model\Convert\Order    $convertOrder
     */
    public function __construct(
        Context $context,
        Order $convertOrder,
        ScopeConfigInterface $scopeInterface
    ) {
        parent::__construct($context);
        $this->scopeInterface = $scopeInterface;
        $this->convertOrder = $convertOrder;
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

        // Retrieve the selected HS Code attribute from the configuration
        $hsCodeAttribute = $this->scopeInterface->getValue('lpc_advanced/lpc_labels/hs_code_attribute', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        // Set default attribute if configuration value is empty
        if (!$hsCodeAttribute) {
            $hsCodeAttribute = 'lpc_hs_code';
        }

        $order = $shipment->getOrder();
        foreach ($shipment->getAllItems() as $item) {
            $qtyToShip = $item->getQty();

            $package['params']['weight'] += $qtyToShip * $item->getWeight();
            $package['params']['customs_value'] += $qtyToShip * $item->getPrice();

            $orderItem = $item->getOrderItem();
            if (empty($order)) {
                $order = $orderItem->getOrder();
            }
            // Fetch the HS code using the selected attribute from the product
            $hsCodeValue = $orderItem->getProduct()->getData($hsCodeAttribute);

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
                'lpc_hs_code' => $hsCodeValue,
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
                'lpc_hs_code'            => $item->getProduct()->getLpcHsCode(),
            ];
        }

        return [$package];
    }
}
