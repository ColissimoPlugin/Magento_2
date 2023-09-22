<?php

namespace LaPoste\Colissimo\Block\Adminhtml\Order;

use \LaPoste\Colissimo\Model\Carrier\Colissimo;
use \Magento\Sales\Api\ShipmentRepositoryInterface;

class TrackingDimension extends \Magento\Backend\Block\Template
{
    protected $_template = 'LaPoste_Colissimo::order/trackingDimension.phtml';
    protected $_coreRegistry = null;
    protected $shipmentRepository;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        ShipmentRepositoryInterface $shipmentRepository,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->shipmentRepository = $shipmentRepository;
        parent::__construct($context, $data);
    }

    public function isDdpMethod()
    {
        $currentOrder = $this->getOrderFromShipment();
        $shippingMethod = $currentOrder->getShippingMethod();

        if (in_array($shippingMethod, Colissimo::DDP_METHODS)) {
            return true;
        }

        return false;
    }

    public function getShipment()
    {
        $shipment = $this->_coreRegistry->registry('current_shipment');
        if (empty($shipment)) {
            $data = $this->getRequest()->getParam('lpcDdp');
            $shipment = $this->shipmentRepository->get($data['shipment_id']);
        }

        return $shipment;
    }

    protected function getOrderFromShipment()
    {
        return $this->getShipment()->getOrder();
    }

    public function getDdpData()
    {
        $shipment = $this->getShipment();

        if (empty($shipment)) {
            return [
                'description' => '',
                'height'      => '',
                'width'       => '',
                'length'      => '',
            ];
        }

        return [
            'description' => $shipment->getLpcDdpDescription(),
            'height'      => $shipment->getLpcDdpHeight(),
            'width'       => $shipment->getLpcDdpWidth(),
            'length'      => $shipment->getLpcDdpLength(),
        ];
    }

    public function getSubmitUrl()
    {
        return $this->getUrl('laposte_colissimo/shipment/addDdpData', ['id' => $this->getShipment()->getId()]);
    }

    protected function _prepareLayout()
    {
        $this->addChild(
            'submit_ddp',
            \Magento\Backend\Block\Widget\Button::class,
            ['id' => 'submit_ddp_button', 'label' => __('Save DDP data'), 'class' => 'action-secondary save']
        );

        return parent::_prepareLayout();
    }
}
