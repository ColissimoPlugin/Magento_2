<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Shipment;

use LaPoste\Colissimo\Helper\Shipment;
use LaPoste\Colissimo\Logger\Colissimo;
use LaPoste\Colissimo\Model\Shipping\ReturnLabelGenerator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Shipping\Model\Shipping\LabelGenerator;

class GenerateLabel extends Action
{
    const ADMIN_RESOURCE = 'LaPoste_Colissimo::shipment';
    const LABEL_TYPE_OUTWARD = 'outward';

    protected $request;
    protected LabelGenerator $labelGenerator;
    protected ReturnLabelGenerator $returnLabelGenerator;
    protected $shipmentHelper;
    protected $shipmentRepository;
    protected $messageManager;
    protected $logger;

    public function __construct(
        Context $context,
        ShipmentRepository $shipmentRepository,
        LabelGenerator $labelGenerator,
        ReturnLabelGenerator $returnLabelGenerator,
        Shipment $shipmentHelper,
        ManagerInterface $messageManager,
        Colissimo $logger
    ) {
        parent::__construct($context);

        $this->request = $context->getRequest();
        $this->labelGenerator = $labelGenerator;
        $this->returnLabelGenerator = $returnLabelGenerator;
        $this->shipmentHelper = $shipmentHelper;
        $this->messageManager = $messageManager;
        $this->shipmentRepository = $shipmentRepository;
        $this->logger = $logger;
    }

    public function execute()
    {
        $shipmentId = $this->request->getParam('shipment_id');
        $labelType = $this->request->getParam('label_type');
        $shipment = $this->shipmentRepository->get($shipmentId);

        $packages = $this->shipmentHelper->shipmentToPackages($shipment);

        $this->request->setParam('packages', $packages);
        try {
            if (self::LABEL_TYPE_OUTWARD === $labelType) {
                $this->labelGenerator->create($shipment, $this->request);
                $successMessage = __('Shipping labels have been generated.');
            } else {
                $this->returnLabelGenerator->createReturnLabel($shipment, $this->request);
                $successMessage = __('Inward shipping label have been generated.');
            }
            $shipment->save();
            $this->messageManager->addSuccessMessage($successMessage);
        } catch (\Exception $e) {
            $message = __('Could not generate label for shipment #%1: ', $shipment->getIncrementId()) . ' ' . $e->getMessage();

            $this->messageManager->addErrorMessage($message);
            $this->logger->error($message);
        }

        return $this->_redirect('laposte_colissimo/shipment/index');
    }
}
