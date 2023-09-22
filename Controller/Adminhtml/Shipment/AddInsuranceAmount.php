<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RawFactory;

class AddInsuranceAmount extends Action
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
        $data = $this->getRequest()->getParam('lpcInsurance');
        $shipment = $this->shipmentRepository->get($data['shipment_id']);
        $insuranceAmount = null;
        if (!empty($data['lpc_use_insurance']) && !empty($data['lpc_insurance_amount'])) {
            $insuranceAmount = $data['lpc_insurance_amount'];
        }
        $shipment->setDataUsingMethod('lpc_insurance_amount', $insuranceAmount);
        $shipment->save();

        $resultPage = $this->resultPageFactory->create();
        $block = $resultPage->getLayout()->getBlock('lpc_shipment_custom_insurance');
        if (!$block) {
            $block = $resultPage->getLayout()->createBlock('\LaPoste\Colissimo\Block\Adminhtml\Order\CustomInsurance');
        }

        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setContents($block->toHtml());

        return $resultRaw;
    }
}
