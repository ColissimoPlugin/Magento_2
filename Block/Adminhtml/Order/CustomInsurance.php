<?php

namespace LaPoste\Colissimo\Block\Adminhtml\Order;

use \LaPoste\Colissimo\Model\Carrier\Colissimo;
use \LaPoste\Colissimo\Helper\Data;
use \Magento\Sales\Api\ShipmentRepositoryInterface;

class CustomInsurance extends \Magento\Backend\Block\Template
{
    protected $_template = 'LaPoste_Colissimo::order/customInsurance.phtml';
    protected $_coreRegistry = null;
    protected $shipmentRepository;
    protected $helperData;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        ShipmentRepositoryInterface $shipmentRepository,
        Data $helperData,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->shipmentRepository = $shipmentRepository;
        $this->helperData = $helperData;
        parent::__construct($context, $data);
    }

    public function getShipment()
    {
        $shipment = $this->_coreRegistry->registry('current_shipment');
        if (empty($shipment)) {
            $data = $this->getRequest()->getParam('lpcInsurance');
            $shipment = $this->shipmentRepository->get($data['shipment_id']);
        }

        return $shipment;
    }

    public function getInsurancePrices()
    {
        $prices = ['150', '300', '500', '1000'];
        $currentOrder = $this->getOrderFromShipment();
        $shippingMethod = $currentOrder->getShippingMethod();
        if ($shippingMethod !== Colissimo::CODE . '_' . Colissimo::CODE_SHIPPING_METHOD_RELAY) {
            $prices = array_merge($prices, ['2000', '5000']);
        }

        return $prices;
    }

    protected function getOrderFromShipment()
    {
        return $this->getShipment()->getOrder();
    }

    public function getInsuranceAmount()
    {
        $shipment = $this->getShipment();

        if (empty($shipment)) {
            return null;
        }

        return $shipment->getLpcInsuranceAmount();
    }

    public function getInsuranceOption()
    {
        return $this->helperData->getAdvancedConfigValue('lpc_labels/isUsingInsurance');
    }

    public function getSubmitUrl()
    {
        return $this->getUrl('laposte_colissimo/shipment/addInsuranceAmount', ['id' => $this->getShipment()->getId()]);
    }

    protected function _prepareLayout()
    {
        $this->addChild(
            'submit_insurance',
            \Magento\Backend\Block\Widget\Button::class,
            ['id' => 'submit_insurance_button', 'label' => __('Save insurance amount'), 'class' => 'action-secondary save']
        );

        return parent::_prepareLayout();
    }
}
