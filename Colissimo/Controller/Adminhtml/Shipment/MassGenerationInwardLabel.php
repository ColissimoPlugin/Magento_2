<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Shipment;

use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Helper\Shipment;
use LaPoste\Colissimo\Model\Shipping\ReturnLabelGenerator;
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

    protected $request;
    protected CollectionFactory $shipmentCollection;
    protected ReturnLabelGenerator $labelGenerator;
    protected Shipment $shipmentHelper;
    protected Filter $filter;
    protected Data $helperData;

    /**
     * @param Context              $context
     * @param CollectionFactory    $shipmentCollection
     * @param ReturnLabelGenerator $labelGenerator
     * @param Shipment             $shipmentHelper
     * @param Filter               $filter
     * @param Data                 $helperData
     */
    public function __construct(
        Context $context,
        CollectionFactory $shipmentCollection,
        ReturnLabelGenerator $labelGenerator,
        Shipment $shipmentHelper,
        Filter $filter,
        Data $helperData
    ) {
        $this->request = $context->getRequest();
        $this->shipmentCollection = $shipmentCollection;
        $this->labelGenerator = $labelGenerator;
        $this->shipmentHelper = $shipmentHelper;
        $this->filter = $filter;
        $this->helperData = $helperData;
        parent::__construct($context);
    }

    public function execute()
    {
        $customersCanSelfReturn = $this->helperData->getAdvancedConfigValue('lpc_return_labels/availableToCustomer');
        $securedReturn = $this->helperData->getAdvancedConfigValue('lpc_return_labels/securedReturn');

        if ($customersCanSelfReturn && $securedReturn) {
            $this->messageManager->addErrorMessage(
                __('You cannot generate return labels while the secured return is active, only customers can generate them from their account.')
            );

            return $this->resultRedirectFactory->create()->setPath('laposte_colissimo/shipment/');
        }

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
