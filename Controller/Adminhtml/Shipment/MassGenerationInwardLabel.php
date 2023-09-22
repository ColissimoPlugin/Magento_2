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

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
use LaPoste\Colissimo\Model\Carrier\Colissimo;
use Magento\Ui\Component\MassAction\Filter;

class MassGenerationInwardLabel extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'LaPoste_Colissimo::shipment';

    protected $shipmentCollection;
    protected $request;
    protected $labelGenerator;
    protected $shipmentHelper;
    protected $filter;

    /**
     * @param Context                                                             $context
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollection
     * @param \LaPoste\Colissimo\Model\Shipping\ReturnLabelGenerator              $labelGenerator
     * @param \LaPoste\Colissimo\Helper\Shipment                                  $shipmentHelper
     */
    public function __construct(
        Context $context,
        CollectionFactory $shipmentCollection,
        \LaPoste\Colissimo\Model\Shipping\ReturnLabelGenerator $labelGenerator,
        \LaPoste\Colissimo\Helper\Shipment $shipmentHelper,
        Filter $filter
    ) {
        $this->shipmentCollection = $shipmentCollection;
        $this->request = $context->getRequest();
        $this->labelGenerator = $labelGenerator;
        $this->shipmentHelper = $shipmentHelper;
        $this->filter = $filter;
        parent::__construct($context);
    }

    public function execute()
    {
        $shipments = $this->filter->getCollection($this->shipmentCollection->create());

        // Magento 2.1 returns null for param selected if all selected
        // In this case we will get all shipments and filter non Colissimo ones later
        if (!empty($this->getRequest()->getParam('selected'))) {
            $shipments = $shipments->addFieldToFilter('entity_id', $this->getRequest()->getParam('selected'));
        }

        $isError = false;
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        foreach ($shipments as $shipment) {
            $shippingMethod = $shipment->getOrder()->getShippingMethod();
            if (false === strpos($shippingMethod, Colissimo::CODE . '_')) {
                continue; // Remove non colissimo shipments
            }

            try {
                $this->generateLabel($shipment);
            } catch (\Exception $e) {
                $isError = true;
                $this->messageManager->addErrorMessage(
                    __('While generating label for shipment #%1: ', $shipment->getIncrementId())
                    . $e->getMessage()
                );
            }
        }

        if ($isError === false) {
            $this->messageManager->addSuccessMessage(__('Shipping labels have been generated.'));
        }

        return $this->resultRedirectFactory->create()->setPath('laposte_colissimo/shipment/');
    }

    protected function generateLabel(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $packages = $this->shipmentHelper
            ->shipmentToPackages($shipment);


        $this->request->setParam('packages', $packages);

        $this->labelGenerator->createReturnLabel($shipment, $this->request);
        $shipment->save();

        return $this->_redirect('laposte_colissimo/shipment/index');
    }
}
