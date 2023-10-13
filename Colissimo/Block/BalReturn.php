<?php

namespace LaPoste\Colissimo\Block;

class BalReturn extends \Magento\Framework\View\Element\Template
{
    protected $addressRenderer;
    protected $customerSession;
    protected $shipmentRepository;
    protected $request;

    protected $labellingApi;
    protected $generateLabelPayload;
    protected $labelGenerator;
    protected $shipmentHelper;

    private $shipment;
    private $listMailBoxPickingDatesResponse;
    private $returnTrackingNumber;
    private $pickUpConfirmation;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload $generateLabelPayload,
        \LaPoste\Colissimo\Api\Carrier\LabellingApi $labellingApi,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \LaPoste\Colissimo\Model\Shipping\ReturnLabelGenerator $labelGenerator,
        \Magento\Customer\Model\Session $customerSession,
        \LaPoste\Colissimo\Helper\Shipment $shipmentHelper
    ) {
        parent::__construct($context);

        $this->generateLabelPayload = $generateLabelPayload;
        $this->labellingApi = $labellingApi;
        $this->shipmentRepository = $shipmentRepository;
        $this->addressRenderer = $addressRenderer;
        $this->customerSession = $customerSession;
        $this->request = $context->getRequest();
        $this->labelGenerator = $labelGenerator;
        $this->shipmentHelper = $shipmentHelper;
    }

    public function getShipment()
    {
        if (null === $this->shipment) {
            $shipmentId = $this->getRequest()->getParam('shipmentId');

            $this->shipment = $this->shipmentRepository
                ->get($shipmentId);
        }

        if ($this->shipment->getOrder()->getCustomerId() != $this->customerSession->getCustomer()->getId()) {
            throw new \Exception('You are not allowed to access this resource!');
        }


        return $this->shipment;
    }

    public function addressFormatter(\Magento\Sales\Model\Order\Address $address, $type = 'html')
    {
        return $this->addressRenderer->format($address, $type);
    }


    public function formatFormAddress()
    {
        $address = $this->getRequest()->getParam('address');

        return <<<END_HTML
<div class="lpc_balreturn_return_address">
        {$address['companyName']}<br/>
        {$address['street']}<br/>
        {$address['zipCode']}, {$address['city']}<br/>
        FR<br/>
</div>
END_HTML;
    }

    public function getListMailBoxPickingDatesResponse()
    {
        if (null === $this->listMailBoxPickingDatesResponse) {
            $sender = $this->getRequest()->getParam('address');
            $sender['countryCode'] = 'FR';

            $payload = $this->generateLabelPayload
                ->withContractNumber()
                ->withPassword()
                ->withSender($sender)
                ->assemble();


            $payload['sender'] = $payload['letter']['sender']['address'];
            unset($payload['letter']);

            try {
                $this->listMailBoxPickingDatesResponse = $this->labellingApi
                    ->listMailBoxPickingDates($payload);
            } catch (\LaPoste\Colissimo\Exception\ApiException $e) {
                $this->listMailBoxPickingDatesResponse = false;
            }
        }

        return $this->listMailBoxPickingDatesResponse;
    }

    public function getMailBoxPickingDate()
    {
        $timestamps = $this->getListMailBoxPickingDatesResponse()
            ->mailBoxPickingDates;

        if (empty($timestamps)) {
            return null;
        } else {
            $date = \DateTime::createFromFormat(
                'U',
                $timestamps[0] / 1000
            );

            return $this->formatDate(
                $date,
                \IntlDateFormatter::LONG,
                false
            );
        }
    }


    public function sendPickUpConfirmation()
    {
        $returnTrackingNumber = $this->getReturnTrackingNumber();

        if (null === $this->pickUpConfirmation) {
            $sender = $this->getRequest()->getParam('address');
            // add email
            $sender['countryCode'] = 'FR';

            $payload = $this->generateLabelPayload
                ->withContractNumber()
                ->withPassword()
                ->withSender($sender)
                ->assemble();


            $payload['sender'] = $payload['letter']['sender']['address'];
            unset($payload['letter']);
            $payload['mailBoxPickingDate'] = $this->getRequest()->getParam('pickingDate');
            $payload['parcelNumber'] = $returnTrackingNumber;

            try {
                $this->pickUpConfirmation = $this->labellingApi
                    ->planPickUp($payload);
            } catch (\LaPoste\Colissimo\Exception\ApiException $e) {
                $this->pickUpConfirmation = __('An error occured while confirming this pick-up!');
            }
        }

        return $this->pickUpConfirmation;
    }

    public function getReturnTrackingNumber()
    {
        if (null === $this->returnTrackingNumber) {
            $shipment = $this->getShipment();


            // Generate the return label
            $packages = $this->shipmentHelper
                ->shipmentToPackages($shipment);


            $this->request
                ->setParam('packages', $packages);

            try {
                $trackingNumbers = $this->labelGenerator->createReturnLabel($shipment, $this->request);
                $shipment->save();


                $this->returnTrackingNumber = $trackingNumbers[0];
            } catch (\LaPoste\Colissimo\Exception\ApiException $e) {
                $this->returnTrackingNumber = __('An error occured while generating the return tracking number!');
            }
        }

        return $this->returnTrackingNumber;
    }
}
