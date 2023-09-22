<?php
/**
 * ******************************************************
 *  * Copyright (C) 2018 La Poste.
 *  *
 *  * This file is part of La Poste - Colissimo module.
 *  *
 *  * La Poste - Colissimo module can not be copied and/or distributed without the express
 *  * permission of La Poste.
 *  ******************************************************
 *
 */

namespace LaPoste\Colissimo\Controller\Adminhtml\Shipment;

use \Magento\Backend\App\Action;
use \Magento\Backend\App\Action\Context;
use \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
use \LaPoste\Colissimo\Logger\Colissimo;
use \LaPoste\Colissimo\Helper\Bordereau;
use LaPoste\Colissimo\Model\Carrier\Colissimo as ColissimoCarrier;
use Magento\Ui\Component\MassAction\Filter;

class MassBordereauGeneration extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'LaPoste_Colissimo::shipment';

    /**
     * @var CollectionFactory
     */
    protected $shipmentCollection;

    /**
     * @var Colissimo
     */
    protected $logger;

    /**
     * @var Bordereau
     */
    protected $bordereauHelper;
    protected $filter;

    /**
     * MassBordereauGeneration constructor.
     *
     * @param Context           $context
     * @param CollectionFactory $shipmentCollection
     * @param Colissimo         $logger
     * @param Bordereau         $bordereauHelper
     */
    public function __construct(
        Context $context,
        CollectionFactory $shipmentCollection,
        Colissimo $logger,
        Bordereau $bordereauHelper,
        Filter $filter
    ) {
        $this->shipmentCollection = $shipmentCollection;
        $this->logger = $logger;
        $this->bordereauHelper = $bordereauHelper;
        $this->filter = $filter;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirectFactory = $this->resultRedirectFactory->create();

        $shipments = $this->filter->getCollection($this->shipmentCollection->create());

        // Magento 2.1 returns null for param selected if all selected
        // In this case we will get all shipments and filter non Colissimo ones later
        if (!empty($this->getRequest()->getParam('selected'))) {
            $shipments = $shipments->addFieldToFilter('entity_id', $this->getRequest()->getParam('selected'));
        }

        $parcelNumbers = $this->shipmentsToParcelNumbers($shipments);

        try {
            if ($this->bordereauHelper->generateBordereau($parcelNumbers)) {
                $this->messageManager->addSuccessMessage(__('Delivery docket has been generated.'));

                return $resultRedirectFactory->setPath('laposte_colissimo/bordereau');
            } else {
                $this->messageManager->addErrorMessage(__('Unable to generate delivery docket') . '.');

                return $resultRedirectFactory->setPath('laposte_colissimo/shipment');
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__, ['exception' => $e]);

            $this->messageManager->addErrorMessage(__('Unable to generate delivery docket') . __(' (see logs for details)') . '.');

            return $resultRedirectFactory->setPath('laposte_colissimo/shipment/');
        }
    }

    /**
     * Get the parcel numbers from the shipment IDs
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipments
     *
     * @return array
     */
    private function shipmentsToParcelNumbers(
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipments
    ) {
        $result = [];

        foreach ($shipments as $shipment) {
            $shippingMethod = $shipment->getOrder()->getShippingMethod();
            if (false === strpos($shippingMethod, ColissimoCarrier::CODE . '_')) {
                continue; // Remove non colissimo shipments
            }

            $tracks = $shipment->getAllTracks();
            $lastOutwardTrack = $this->getLastOutwardTrack($tracks);

            if ($lastOutwardTrack) {
                $result[] = $lastOutwardTrack->getNumber();
            }
        }

        sort($result, SORT_STRING);

        return $result;
    }

    private function getLastOutwardTrack(array $tracks)
    {
        foreach (array_reverse($tracks) as $track) {
            if ('R' !== substr($track->getNumber(), 1, 1)) {
                return $track;
            }
        }
    }
}
