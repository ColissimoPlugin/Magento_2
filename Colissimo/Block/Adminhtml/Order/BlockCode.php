<?php

namespace LaPoste\Colissimo\Block\Adminhtml\Order;

use LaPoste\Colissimo\Model\Carrier\Colissimo;
use LaPoste\Colissimo\Model\AccountApi;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Button;

class BlockCode extends Template
{
    protected $_template = 'LaPoste_Colissimo::order/blockCode.phtml';
    protected $_coreRegistry = null;
    protected $shipmentRepository;
    private AccountApi $accountApi;

    public function __construct(
        Context $context,
        Registry $registry,
        ShipmentRepositoryInterface $shipmentRepository,
        AccountApi $accountApi,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_coreRegistry = $registry;
        $this->shipmentRepository = $shipmentRepository;
        $this->accountApi = $accountApi;
    }

    public function isBlockCodeActive(): bool
    {
        $currentOrder = $this->getShipment()->getOrder();
        $shippingCountryCode = $currentOrder->getShippingAddress()->getCountryId();

        if ('FR' !== $shippingCountryCode) {
            return false;
        }

        $shippingMethod = $currentOrder->getShippingMethod();
        if (!in_array(
            $shippingMethod,
            [
                Colissimo::CODE . '_' . Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS,
                Colissimo::CODE . '_' . Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS_DDP,
            ]
        )) {
            return false;
        }

        $accountInformation = $this->accountApi->getAccountInformation();

        return !empty($accountInformation['statutCodeBloquant']);
    }

    public function getShipment()
    {
        $shipment = $this->_coreRegistry->registry('current_shipment');
        if (empty($shipment)) {
            $data = $this->getRequest()->getParam('lpcBlockCode');
            $shipment = $this->shipmentRepository->get($data['shipment_id']);
        }

        return $shipment;
    }

    public function getBlockCodeStatus(): bool
    {
        $shipment = $this->getShipment();

        if (empty($shipment)) {
            return false;
        }

        return 'disabled' !== $shipment->getLpcBlockCode();
    }

    public function getSubmitUrl()
    {
        return $this->getUrl('laposte_colissimo/shipment/addBlockCodeStatus', ['id' => $this->getShipment()->getId()]);
    }

    protected function _prepareLayout()
    {
        $this->addChild(
            'submit_block_code',
            Button::class,
            [
                'id' => 'submit_block_code_button',
                'label' => __('Save blocking code status'),
                'class' => 'action-secondary save',
            ]
        );

        return parent::_prepareLayout();
    }
}
