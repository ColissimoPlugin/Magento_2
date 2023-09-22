<?php

namespace LaPoste\Colissimo\Block\Coliship;

class Export extends \Magento\Framework\View\Element\Template
{
    protected $helperData;
    protected $shipmentRepository;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \LaPoste\Colissimo\Helper\Data $helperData,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
    ) {
        parent::__construct($context);

        $this->helperData = $helperData;
        $this->shipmentRepository = $shipmentRepository;
    }


    public function isUsingColiship()
    {
        return (bool) $this->helperData
            ->getAdvancedConfigValue('lpc_labels/isUsingColiship');
    }

    public function getExportUrl()
    {
        return $this->getUrl(
            $this->helperData
                ->getAdminRoute('coliship', 'export')
        );
    }
}
