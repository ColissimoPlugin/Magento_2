<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
use LaPoste\Colissimo\Logger\Colissimo as ColissimoLogger;
use LaPoste\Colissimo\Model\Carrier\Colissimo;
use Magento\Ui\Component\MassAction\Filter;

class MassDeleteLabels extends Action
{
    const ADMIN_RESOURCE = 'LaPoste_Colissimo::shipment';

    protected $request;
    protected $shipmentCollection;
    protected $searchCriteriaBuilder;
    protected $logger;
    protected $filter;

    public function __construct(
        Context $context,
        CollectionFactory $shipmentCollection,
        ColissimoLogger $logger,
        Filter $filter
    ) {
        $this->shipmentCollection = $shipmentCollection;
        $this->logger = $logger;
        $this->filter = $filter;

        parent::__construct($context);
    }

    public function execute()
    {
        $shipments = $this->filter->getCollection($this->shipmentCollection->create());

        if (!empty($this->getRequest()->getParam('selected'))) {
            $shipments = $shipments->addFieldToFilter('entity_id', $this->getRequest()->getParam('selected'));
        }

        $isError = false;

        foreach ($shipments as $shipment) {
            $shippingMethod = $shipment->getOrder()->getShippingMethod();
            if (false === strpos($shippingMethod, Colissimo::CODE . '_')) {
                continue; // Ignore non colissimo shipments
            }

            try {
                // Delete Magento track entities
                $shipmentTracks = $shipment->getAllTracks();
                foreach ($shipmentTracks as $oneTrack) {
                    $oneTrack->delete();
                }

                // Delete content of the label, CN23, tracking status
                $shipment->setShippingLabel(null);
                $shipment->setLpcReturnLabel(null);
                $shipment->setLpcLabelCn23(null);
                $shipment->setLpcLabelDocs(null);
                $shipment->setPackages([]);
                $shipment->setShipmentStatus(null);
                $shipment->save();
            } catch (\Exception $e) {
                $isError = true;
                $msg = __('An error occurred while deleting label for shipment #%1: ', $shipment->getIncrementId()) . $e->getMessage();
                $this->logger->debug($msg, []);
                $this->messageManager->addErrorMessage($msg);
            }
        }

        if ($isError === false) {
            $this->messageManager->addSuccessMessage(__('Shipping labels have been deleted.'));
        }

        return $this->resultRedirectFactory->create()->setPath('laposte_colissimo/shipment/');
    }
}
