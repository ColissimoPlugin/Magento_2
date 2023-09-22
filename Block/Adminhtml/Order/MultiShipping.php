<?php

namespace LaPoste\Colissimo\Block\Adminhtml\Order;

use \Magento\Sales\Api\ShipmentRepositoryInterface;
use LaPoste\Colissimo\Helper\CountryOffer;

class MultiShipping extends \Magento\Backend\Block\Template
{
    protected $_template = 'LaPoste_Colissimo::order/multiShipping.phtml';
    protected $_coreRegistry = null;
    protected $shipmentRepository;
    protected $helperData;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        ShipmentRepositoryInterface $shipmentRepository,
        \LaPoste\Colissimo\Helper\Data $helperData,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->shipmentRepository = $shipmentRepository;
        $this->helperData = $helperData;
        parent::__construct($context, $data);
    }

    public function isOmShipment()
    {
        $currentOrder = $this->getOrderFromShipment();
        $shippingCountryCode = $currentOrder->getShippingAddress()->getCountryId();

        return in_array($shippingCountryCode, array_merge(CountryOffer::DOM1_COUNTRIES_CODE, CountryOffer::DOM2_COUNTRIES_CODE));
    }

    public function getOrderFromShipment()
    {
        return $this->getShipment()->getOrder();
    }

    public function getOrderQty()
    {
        $order = $this->getOrderFromShipment();

        return $order->getTotalQtyOrdered();
    }

    public function getShipment()
    {
        $shipment = $this->_coreRegistry->registry('current_shipment');
        if (empty($shipment)) {
            $data = $this->getRequest()->getParam('lpcMultiShipping');
            $shipment = $this->shipmentRepository->get($data['shipment_id']);
        }

        return $shipment;
    }

    public function getSubmitUrl()
    {
        return $this->getUrl('laposte_colissimo/shipment/addMultiShippingData', ['id' => $this->getShipment()->getId()]);
    }

    protected function _prepareLayout()
    {
        $this->addChild(
            'submit_multiShipping',
            \Magento\Backend\Block\Widget\Button::class,
            ['id' => 'submit_multiShipping_button', 'label' => __('Save multi parcels data'), 'class' => 'action-secondary save']
        );

        return parent::_prepareLayout();
    }

    public function getMultiShippingData()
    {
        $order = $this->getOrderFromShipment();

        if (empty($order)) {
            return [
                'amount' => '',
            ];
        }

        return [
            'amount' => $order->getLpcMultiParcelsAmount(),
        ];
    }
}
