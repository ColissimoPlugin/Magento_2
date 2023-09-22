<?php

namespace LaPoste\Colissimo\Plugin\Adminhtml\Order\Shipment;

use \LaPoste\Colissimo\Model\Carrier\Colissimo;
use \LaPoste\Colissimo\Model\Carrier\GenerateLabelPayload;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Sales\Api\ShipmentRepositoryInterface;
use \Magento\Framework\Registry;

class Save
{
    protected $orderRepository;
    protected $shipmentRepository;
    protected $_coreRegistry;
    protected $logger;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ShipmentRepositoryInterface $shipmentRepository,
        \LaPoste\Colissimo\Logger\Colissimo $logger,
        Registry $registry
    ) {
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->_coreRegistry = $registry;
        $this->logger = $logger;
    }

    /**
     * @param $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterExecute($subject, $result)
    {
        $request = $subject->getRequest();
        $orderId = $request->getParams()['order_id'];

        if (empty($orderId)) {
            return $result;
        }

        $shipment = $this->_coreRegistry->registry('current_shipment');
        if (empty($shipment)) {
            $data = $request->getParam('lpcInsurance');
            if (!empty($data['shipment_id'])) {
                $shipment = $this->shipmentRepository->get($data['shipment_id']);
            }
        }
        if (empty($shipment)) {
            return $result;
        }

        $order = $this->orderRepository->get($orderId);
        $shippingMethod = $order->getShippingMethod();
        if (in_array($shippingMethod, Colissimo::DDP_METHODS)) {
            $shipmentData = $subject->getRequest()->getParam('lpcDdp');
            $ddpDescription = !empty($shipmentData['lpc_ddp_description']) ? $shipmentData['lpc_ddp_description'] : null;
            $ddpLength = !empty($shipmentData['lpc_ddp_length']) ? $shipmentData['lpc_ddp_length'] : null;
            $ddpWidth = !empty($shipmentData['lpc_ddp_width']) ? $shipmentData['lpc_ddp_width'] : null;
            $ddpHeight = !empty($shipmentData['lpc_ddp_height']) ? $shipmentData['lpc_ddp_height'] : null;
            $shipment->setDataUsingMethod('lpc_ddp_description', $ddpDescription);
            $shipment->setDataUsingMethod('lpc_ddp_length', $ddpLength);
            $shipment->setDataUsingMethod('lpc_ddp_width', $ddpWidth);
            $shipment->setDataUsingMethod('lpc_ddp_height', $ddpHeight);
        }

        $insuranceData = $subject->getRequest()->getParam('lpcInsurance');
        $insuranceAmount = null;
        if (!empty($insuranceData['lpc_use_insurance']) && !empty($insuranceData['lpc_insurance_amount'])) {
            $insuranceAmount = $insuranceData['lpc_insurance_amount'];
        }
        $shipment->setDataUsingMethod('lpc_insurance_amount', $insuranceAmount);

        $multiShippingData = $subject->getRequest()->getParam('lpcMultiShipping');
        $shipmentData = $subject->getRequest()->getParam('shipment');
        $parcelsAmount = $order->getLpcMultiParcelsAmount();
        // If we checked the checkbox to use multi shipping or that there is already an amount
        if (!empty($parcelsAmount) || (!empty($multiShippingData['lpc_use_multi_parcels']) && $multiShippingData['lpc_use_multi_parcels'] === 'on')) {
            $countShipments = count($order->getShipmentsCollection());

            // When we create a shipping label on the shipment creation the line above doesn't return the right amount
            if (!empty($shipmentData) && !empty($shipmentData['create_shipping_label']) && $shipmentData['create_shipping_label'] == 1) {
                $countShipments ++;
            }
            // If it's the first shipment
            if ($countShipments === 1) {
                $parcelsAmount = $multiShippingData['lpc_multi_parcels_amount'];
                if ($parcelsAmount < 5 && $parcelsAmount > 1) {
                    $order->setLpcMultiParcelsAmount($parcelsAmount)->save();
                } else {
                    $this->messageManager->addErrorMessage(
                        __('If you use the multi shipping, the amount of parcels must be between 2 and 4')
                    );

                    return $result;
                }
            }

            // Shipment already created so the number of shipment and number of parcels are equals
            // We handle on the label generation the error if there is more shipment than allowed
            $shipment->setDataUsingMethod('lpc_multi_parcels_number', $countShipments);

            $type = $countShipments == $parcelsAmount ? GenerateLabelPayload::LABEL_TYPE_MASTER : GenerateLabelPayload::LABEL_TYPE_FOLLOWER;
            $shipment->setDataUsingMethod('lpc_shipping_type', $type);
        } else {
            $shipment->setDataUsingMethod('lpc_shipping_type', GenerateLabelPayload::LABEL_TYPE_CLASSIC);
        }

        $shipment->save();

        return $result;
    }
}
