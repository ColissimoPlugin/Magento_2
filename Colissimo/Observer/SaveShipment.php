<?php

namespace LaPoste\Colissimo\Observer;

use LaPoste\Colissimo\Model\Carrier\Colissimo;
use LaPoste\Colissimo\Model\Carrier\GenerateLabelPayload;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Shipment;

class SaveShipment implements ObserverInterface
{
    protected ManagerInterface $messageManager;
    protected RequestInterface $request;

    public function __construct(
        ManagerInterface $messageManager,
        RequestInterface $request
    ) {
        $this->messageManager = $messageManager;
        $this->request = $request;
    }

    public function execute(Observer $observer): void
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        $shippingMethod = $order->getShippingMethod();

        // Only run for Colissimo methods
        if (!str_starts_with($shippingMethod, Colissimo::CODE . '_')) {
            return;
        }

        $this->handleInsurance($shipment);
        $this->handleDdp($shipment, $shippingMethod);
        $this->handleSecureCode($shipment);
        $this->handleMultiParcels($shipment, $order);
    }

    private function handleInsurance(Shipment $shipment): void
    {
        $insuranceData = $this->request->getParam('lpcInsurance');
        if (empty($insuranceData)) {
            return;
        }

        $insuranceAmount = null;
        if (!empty($insuranceData['lpc_use_insurance']) && !empty($insuranceData['lpc_insurance_amount'])) {
            $insuranceAmount = $insuranceData['lpc_insurance_amount'];
        }

        $shipment->setDataUsingMethod('lpc_insurance_amount', $insuranceAmount);
    }

    private function handleDdp(Shipment $shipment, string $shippingMethod): void
    {
        if (!in_array($shippingMethod, Colissimo::DDP_METHODS)) {
            return;
        }

        $ddpData = $this->request->getParam('lpcDdp');
        if (empty($ddpData)) {
            return;
        }

        $shipment->setDataUsingMethod('lpc_ddp_description', !empty($ddpData['lpc_ddp_description']) ? $ddpData['lpc_ddp_description'] : null);
        $shipment->setDataUsingMethod('lpc_ddp_length', !empty($ddpData['lpc_ddp_length']) ? $ddpData['lpc_ddp_length'] : null);
        $shipment->setDataUsingMethod('lpc_ddp_width', !empty($ddpData['lpc_ddp_width']) ? $ddpData['lpc_ddp_width'] : null);
        $shipment->setDataUsingMethod('lpc_ddp_height', !empty($ddpData['lpc_ddp_height']) ? $ddpData['lpc_ddp_height'] : null);
    }

    private function handleSecureCode(Shipment $shipment): void
    {
        $blockCodeData = $this->request->getParam('lpcBlockCode');
        if (empty($blockCodeData)) {
            return;
        }

        $shipment->setDataUsingMethod('lpc_block_code', empty($blockCodeData['lpc_block_code']) ? 'enabled' : $blockCodeData['lpc_block_code']);
    }

    private function handleMultiParcels(Shipment $shipment, OrderInterface $order): void
    {
        $multiShippingData = $this->request->getParam('lpcMultiShipping');
        if (empty($multiShippingData)) {
            return;
        }

        $parcelsAmount = (int) $order->getLpcMultiParcelsAmount();
        if (empty($parcelsAmount) && (empty($multiShippingData['lpc_use_multi_parcels']) || $multiShippingData['lpc_use_multi_parcels'] !== 'on')) {
            $shipment->setDataUsingMethod('lpc_shipping_type', GenerateLabelPayload::LABEL_TYPE_CLASSIC);

            return;
        }

        $countShipments = count($order->getShipmentsCollection());

        // When we create a shipping label on the shipment creation the line above doesn't return the right amount
        $shipmentData = $this->request->getParam('shipment');
        if (!empty($shipmentData) && !empty($shipmentData['create_shipping_label']) && $shipmentData['create_shipping_label'] == 1) {
            $countShipments ++;
        }

        // If it's the first shipment
        if ($countShipments === 1) {
            $parcelsAmount = (int) $multiShippingData['lpc_multi_parcels_amount'];

            if ($parcelsAmount < 2 || $parcelsAmount > 4) {
                $this->messageManager->addErrorMessage(
                    __('If you use the multi shipping, the amount of parcels must be between 2 and 4')
                );

                return;
            }

            $order->setLpcMultiParcelsAmount($parcelsAmount)->save();
        }

        // Shipment already created so the number of shipment and number of parcels are equals
        // We handle on the label generation the error if there is more shipment than allowed
        $shipment->setDataUsingMethod('lpc_multi_parcels_number', $countShipments);

        $type = $countShipments === $parcelsAmount ? GenerateLabelPayload::LABEL_TYPE_MASTER : GenerateLabelPayload::LABEL_TYPE_FOLLOWER;
        $shipment->setDataUsingMethod('lpc_shipping_type', $type);
    }
}
