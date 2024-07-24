<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RawFactory;

class AddBlockCodeStatus extends Action
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
     * AddBlockCodeStatus constructor.
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
        $data = $this->getRequest()->getParam('lpcBlockCode');
        $shipment = $this->shipmentRepository->get($data['shipment_id']);
        $blockCodeStatus = 'enabled';
        if (!empty($data['lpc_block_code'])) {
            $blockCodeStatus = $data['lpc_block_code'];
        }
        $shipment->setDataUsingMethod('lpc_block_code', $blockCodeStatus);
        $shipment->save();

        $resultPage = $this->resultPageFactory->create();
        $block = $resultPage->getLayout()->getBlock('lpc_shipment_block_code');
        if (!$block) {
            $block = $resultPage->getLayout()->createBlock('\LaPoste\Colissimo\Block\Adminhtml\Order\BlockCode');
        }

        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setContents($block->toHtml());

        return $resultRaw;
    }
}
