<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RawFactory;

class AddDdpData extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'LaPoste_Colissimo::shipment';

    protected $shipmentRepository;
    protected $resultPageFactory;
    protected $resultRawFactory;

    /**
     * AddDdpData constructor.
     *
     * @param Context                     $context
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param PageFactory                 $resultPageFactory
     * @param RawFactory                  $resultRawFactory
     */
    public function __construct(
        Context $context,
        ShipmentRepositoryInterface $shipmentRepository,
        PageFactory $resultPageFactory,
        RawFactory $resultRawFactory
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRawFactory = $resultRawFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getParam('lpcDdp');
        $shipment = $this->shipmentRepository->get($data['shipment_id']);
        $shipment->setDataUsingMethod('lpc_ddp_description', !empty($data['lpc_ddp_description']) ? $data['lpc_ddp_description'] : null);
        $shipment->setDataUsingMethod('lpc_ddp_length', !empty($data['lpc_ddp_length']) ? $data['lpc_ddp_length'] : null);
        $shipment->setDataUsingMethod('lpc_ddp_width', !empty($data['lpc_ddp_width']) ? $data['lpc_ddp_width'] : null);
        $shipment->setDataUsingMethod('lpc_ddp_height', !empty($data['lpc_ddp_height']) ? $data['lpc_ddp_height'] : null);
        $shipment->save();

        $resultPage = $this->resultPageFactory->create();
        $block = $resultPage->getLayout()->getBlock('lpc_shipment_dimension');
        if (!$block) {
            $block = $resultPage->getLayout()->createBlock('\LaPoste\Colissimo\Block\Adminhtml\Order\TrackingDimension');
        }

        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setContents($block->toHtml());

        return $resultRaw;
    }
}
