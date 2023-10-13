<?php

namespace LaPoste\Colissimo\Block;

class BalReturnLink extends \Magento\Shipping\Block\Items
{
    protected $shipmentRepository;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        array $data = []
    ) {
        parent::__construct($context, $registry);

        $this->shipmentRepository = $shipmentRepository;
        $this->coreRegistry = $registry;
    }

    protected function isMailBoxPickUpAllowed()
    {
        return $this->_scopeConfig->getValue('lpc_advanced/lpc_bal/allowMailBoxPickUp');
    }

    public function mailBoxPickUpLink($shipment)
    {
        if (!$this->isMailBoxPickUpAllowed()) {
            return '';
        }

        if ('FR' !== $shipment->getShippingAddress()->getCountryId()) {
            return '';
        }

        if (\LaPoste\Colissimo\Model\Carrier\Colissimo::CODE
            !== $shipment->getOrder()->getShippingMethod(true)->getCarrierCode()) {
            return '';
        }

        return '<a class="action" href="' . $this->getUrl('lpc/balReturn/index', ['shipmentId' => $shipment->getId()]) . '">' . __('MailBox picking return') . '</a>';
    }
}
