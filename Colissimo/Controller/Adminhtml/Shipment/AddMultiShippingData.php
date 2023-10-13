<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RawFactory;

class AddMultiShippingData extends Action
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
     * AddInsuranceAmount constructor.
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
        $data = $this->getRequest()->getParam('lpcMultiShipping');
        $shipment = $this->shipmentRepository->get($data['shipment_id']);
        if (!empty($data['lpc_use_multi_parcels']) && !empty($data['lpc_multi_parcels_amount']) && $data['lpc_multi_parcels_amount'] < 5 && $data['lpc_multi_parcels_amount'] > 1) {
            $numberOfParcels = $data['lpc_multi_parcels_amount'];
            $shipment->setDataUsingMethod('lpc_multi_parcels_amount', $numberOfParcels);
            $shipment->save();
        }

        $resultPage = $this->resultPageFactory->create();
        $block = $resultPage->getLayout()->getBlock('lpc_shipment_multi_shipping');
        if (!$block) {
            $block = $resultPage->getLayout()->createBlock('\LaPoste\Colissimo\Block\Adminhtml\Order\MultiShipping');
        }

        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setContents($block->toHtml());

        return $resultRaw;
    }
}
