<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Model\Carrier;

use \LaPoste\Colissimo\Api\Carrier\OffersApi;
use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Helper\CountryOffer;
use LaPoste\Colissimo\Helper\Pdf;
use LaPoste\Colissimo\Model\Shipping\ReturnLabelGenerator;
use LaPoste\Colissimo\Model\PricesRepository;
use LaPoste\Colissimo\Model\Config\Source\CustomsCategory;
use \Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use \Magento\Shipping\Model\Carrier\CarrierInterface;
use \Magento\Framework\DataObject;
use \Magento\Quote\Model\Quote\Address\RateRequest;
use \Magento\Customer\Model\Session;
use \Magento\Framework\ObjectManagerInterface;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Sales\Model\Order;

class Colissimo extends AbstractCarrierOnline implements CarrierInterface
{
    const CODE = 'colissimo';

    const CODE_SHIPPING_METHOD_RELAY = 'pr';
    const CODE_SHIPPING_METHOD_DOMICILE_SS = 'domiciless';
    const CODE_SHIPPING_METHOD_DOMICILE_AS = 'domicileas';
    const CODE_SHIPPING_METHOD_DOMICILE_AS_DDP = 'domicileasddp';
    const CODE_SHIPPING_METHOD_EXPERT = 'expert';
    const CODE_SHIPPING_METHOD_EXPERT_DDP = 'expertddp';
    const URL_SUIVI_COLISSIMO = "https://www.laposte.fr/outils/suivre-vos-envois?code={lpc_tracking_number}";

    const METHODS_CODES_TRANSLATIONS = [
        self::CODE_SHIPPING_METHOD_DOMICILE_SS     => 'Colissimo Domicile without signature',
        self::CODE_SHIPPING_METHOD_DOMICILE_AS     => 'Colissimo Domicile with signature',
        self::CODE_SHIPPING_METHOD_DOMICILE_AS_DDP => 'Colissimo Domicile with signature - DDP Option',
        self::CODE_SHIPPING_METHOD_RELAY           => 'Colissimo Point Retrait',
        self::CODE_SHIPPING_METHOD_EXPERT          => 'Colissimo International',
        self::CODE_SHIPPING_METHOD_EXPERT_DDP      => 'Colissimo International - DDP Option',
    ];

    const COUNTRIES_DDP = ['BH', 'CA', 'CN', 'EG', 'GB', 'HK', 'ID', 'JP', 'KW', 'MX', 'OM', 'PH', 'SA', 'SG', 'ZA', 'KR', 'CH', 'TH', 'AE', 'US'];

    const DDP_METHODS = [
        self::CODE . '_' . self::CODE_SHIPPING_METHOD_DOMICILE_AS_DDP,
        self::CODE . '_' . self::CODE_SHIPPING_METHOD_EXPERT_DDP,
    ];

    public const PRODUCT_CODE_RELAY = 'HD';
    public const PRODUCT_CODE_WITHOUT_SIGNATURE = 'DOM';
    public const PRODUCT_CODE_WITHOUT_SIGNATURE_OM = 'COM';
    public const PRODUCT_CODE_WITHOUT_SIGNATURE_INTRA_DOM = 'COLD';
    public const PRODUCT_CODE_WITH_SIGNATURE = 'DOS';
    public const PRODUCT_CODE_WITH_SIGNATURE_OM = 'CDS';
    public const PRODUCT_CODE_WITH_SIGNATURE_INTRA_DOM = 'COL';
    public const PRODUCT_CODE_RETURN_FRANCE = 'CORE';
    public const PRODUCT_CODE_RETURN_INT = 'CORI';

    public const ALL_PRODUCT_CODES = [
        self::PRODUCT_CODE_WITH_SIGNATURE_OM,
        self::PRODUCT_CODE_WITH_SIGNATURE_INTRA_DOM,
        self::PRODUCT_CODE_WITHOUT_SIGNATURE_INTRA_DOM,
        self::PRODUCT_CODE_WITHOUT_SIGNATURE_OM,
        self::PRODUCT_CODE_RETURN_FRANCE,
        self::PRODUCT_CODE_RETURN_INT,
        self::PRODUCT_CODE_WITHOUT_SIGNATURE,
        self::PRODUCT_CODE_WITH_SIGNATURE,
        self::PRODUCT_CODE_RELAY,
    ];
    public const PRODUCT_CODE_INSURANCE_AVAILABLE = [
        self::PRODUCT_CODE_WITH_SIGNATURE,
        self::PRODUCT_CODE_WITH_SIGNATURE_OM,
        self::PRODUCT_CODE_WITH_SIGNATURE_INTRA_DOM,
        self::PRODUCT_CODE_RELAY,
        self::PRODUCT_CODE_RETURN_FRANCE,
        self::PRODUCT_CODE_RETURN_INT,
    ];

    protected $_code = self::CODE;

    protected $rateFactory = null;

    protected $rateErrorFactory = null;

    protected $generateLabelPayload;

    protected $labellingApi;

    protected $helperData;

    protected $logger;

    protected $helperCountryOffer;
    /**
     * @var \LaPoste\Colissimo\Helper\Pdf
     */
    protected $helperPdf;
    /**
     * @var \LaPoste\Colissimo\Model\Shipping\ReturnLabelGenerator
     */
    private $returnLabelGenerator;

    protected $customerSession;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    protected $checkoutSession;

    /**
     * @var \LaPoste\Colissimo\Model\Carrier\OffersApi
     */
    protected $offersApi;

    protected $timeZone;

    protected $pricesRepository;
    protected $searchCriteriaBuilder;
    protected $requestInterface;
    protected $requestQuery;

    /**
     * Colissimo constructor.
     *
     * @param \LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload         $generateLabelPayload
     * @param \LaPoste\Colissimo\Api\Carrier\LabellingApi                 $labellingApi
     * @param \LaPoste\Colissimo\Logger\Colissimo                         $colissimoLogger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface          $scopeInterface
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory  $rateErrorFactory
     * @param \Psr\Log\LoggerInterface                                    $logger
     * @param \Magento\Framework\Xml\Security                             $xmlSecurity
     * @param \Magento\Shipping\Model\Simplexml\ElementFactory            $xmlElFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory                  $rateFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory              $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\ErrorFactory        $trackErrorFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory       $trackStatusFactory
     * @param \Magento\Directory\Model\RegionFactory                      $regionFactory
     * @param \Magento\Directory\Model\CountryFactory                     $countryFactory
     * @param \Magento\Directory\Model\CurrencyFactory                    $currencyFactory
     * @param \Magento\Directory\Helper\Data                              $directoryData
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface        $stockRegistry
     * @param \LaPoste\Colissimo\Helper\Data                              $helperData
     * @param \LaPoste\Colissimo\Helper\CountryOffer                      $helperCountryOffer
     * @param \LaPoste\Colissimo\Helper\Pdf                               $helperPdf
     * @param \LaPoste\Colissimo\Model\Shipping\ReturnLabelGenerator      $returnLabelGenerator
     * @param Session                                                     $customerSession
     * @param ObjectManagerInterface                                      $objectManager
     * @param \Magento\Checkout\Model\Session                             $checkoutSession
     * @param OffersApi                                                   $offersApi
     * @param DateTime                                                    $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimeZone                 $timeZone
     * @param PricesRepository                                            $pricesRepository
     * @param SearchCriteriaBuilder                                       $searchCriteriaBuilder
     * @param RequestInterface                                            $requestInterface
     * @param array                                                       $data
     */
    public function __construct(
        \LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload $generateLabelPayload,
        \LaPoste\Colissimo\Api\Carrier\LabellingApi $labellingApi,
        \LaPoste\Colissimo\Logger\Colissimo $colissimoLogger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeInterface,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Xml\Security $xmlSecurity,
        \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        Data $helperData,
        CountryOffer $helperCountryOffer,
        Pdf $helperPdf,
        ReturnLabelGenerator $returnLabelGenerator,
        Session $customerSession,
        ObjectManagerInterface $objectManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        OffersApi $offersApi,
        \Magento\Framework\Stdlib\DateTime\TimeZone $timeZone,
        PricesRepository $pricesRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Framework\App\Request\Http $requestQuery,
        array $data = []
    ) {
        parent::__construct(
            $scopeInterface,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );

        $this->_rateFactory = $rateFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->generateLabelPayload = $generateLabelPayload;
        $this->labellingApi = $labellingApi;
        $this->logger = $colissimoLogger;
        $this->helperData = $helperData;
        $this->helperCountryOffer = $helperCountryOffer;
        $this->helperPdf = $helperPdf;
        $this->returnLabelGenerator = $returnLabelGenerator;
        $this->customerSession = $customerSession;
        $this->objectManager = $objectManager;
        $this->checkoutSession = $checkoutSession;
        $this->offersApi = $offersApi;
        $this->timeZone = $timeZone;
        $this->pricesRepository = $pricesRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->requestInterface = $requestInterface;
        $this->requestQuery = $requestQuery;
    }

    public function getAllowedMethods(): array
    {
        $availableMethods = [];
        foreach (self::METHODS_CODES_TRANSLATIONS as $oneMethodCode => $methodName) {
            if ($this->helperData->getConfigValue('carriers/lpc_group/' . $oneMethodCode . '_enable')) {
                $availableMethods[$oneMethodCode] = $this->helperData->getConfigValue('carriers/lpc_group/' . $oneMethodCode . '_label');
            }
        }

        return $availableMethods;
    }

    public function getTracking($trackings)
    {
        if (!is_array($trackings)) {
            $trackings = [$trackings];
        }

        $result = $this->_trackFactory->create();
        foreach ($trackings as $tracking) {
            $status = $this->_trackStatusFactory->create();
            $status->setCarrier(self::CODE);
            $status->setCarrierTitle($this->getConfigData('title'));
            $status->setTracking($tracking);
            $status->setPopup(1);
            $status->setUrl(str_replace("{lpc_tracking_number}", $tracking, self::URL_SUIVI_COLISSIMO));
            $result->append($status);
        }

        return $result;
    }

    /**
     * @param \Magento\Framework\DataObject $request
     *
     * @return \Magento\Framework\DataObject
     * @throws \Exception
     */
    protected function _doShipmentRequest(DataObject $request)
    {
        $shipment = $request->getOrderShipment();
        $isReturnLabel = $request->getIsReturnLabel();

        try {
            if ($isReturnLabel) {
                // Directly creates return label
                $labelGenerationPayload = $this->mapRequestToReturn($request);
                $returnLabelGenerationPayload = null;
            } else {
                // Creates label
                $labelGenerationPayload = clone $this->mapRequestToShipment($request);

                // If needed will create return label at the same time
                // In this case, we need to revert sender data to correctly build return payload
                $returnLabelGenerationPayload = null;
                if ($this->helperData->getConfigValue(
                    'lpc_advanced/lpc_return_labels/createReturnLabelWithOutward',
                    $request->getStoreId()
                )) {
                    $revertedRequest = $this->revertSenderInfo($request);
                    $returnLabelGenerationPayload = $this->mapRequestToReturn($revertedRequest);
                }
            }

            $result = $this->makeRequest($request, $labelGenerationPayload, $returnLabelGenerationPayload);
            if ($result->hasErrors()) {
                $this->handleLabelErrorMessages($shipment, $isReturnLabel, $result->getErrors());
            } else {
                $this->handleLabelErrorMessages($shipment, $isReturnLabel);
            }
        } catch (\Exception $e) {
            $this->handleLabelErrorMessages($shipment, $isReturnLabel, $e->getMessage());
            throw $e;
        }

        return $result;
    }

    private function handleLabelErrorMessages($shipment, $isReturnLabel, $errorMessage = null)
    {
        $currentMessages = $shipment->getDataUsingMethod('lpc_label_error');
        if (!empty($currentMessages)) {
            $currentMessages = json_decode($currentMessages, \JSON_OBJECT_AS_ARRAY);
        }

        // Separated for json_decode error
        if (empty($currentMessages)) {
            if (empty($errorMessage)) {
                return;
            }
            $currentMessages = [];
        }

        $type = $isReturnLabel ? 'inward' : 'outward';
        if (empty($errorMessage)) {
            unset($currentMessages[$type]);
        } else {
            $currentMessages[$type] = $errorMessage;
        }

        $shipment->setDataUsingMethod('lpc_label_error', json_encode($currentMessages));
        $shipment->save();
    }

    /**
     * Revert shipper and recipient information
     *
     * @param $request
     *
     * @return \Magento\Framework\DataObject
     */
    protected function revertSenderInfo($request)
    {
        $revertedRequest = new DataObject();

        $originalData = $request->getData();
        foreach ($originalData as $key => $value) {
            if (strpos($key, 'shipper_') === 0) {
                $newKey = 'recipient_' . substr($key, 8);
                $revertedRequest->setData($newKey, $value);
            } elseif (strpos($key, 'recipient_') === 0) {
                $newKey = 'shipper_' . substr($key, 10);
                $revertedRequest->setData($newKey, $value);
            } else {
                $revertedRequest->setData($key, $value);
            }
        }

        return $revertedRequest;
    }

    /**
     * @param \Magento\Framework\DataObject                            $request
     * @param \LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload      $labelGenerationPayload
     * @param \LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload|null $returnLabelGenerationPayload
     *
     * @return \Magento\Framework\DataObject
     */
    protected function makeRequest(
        DataObject $request,
        \LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload $labelGenerationPayload,
        \LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload $returnLabelGenerationPayload = null
    ) {
        $result = new DataObject();
        try {
            // Difference between label generation (inward/outward => generateLabel) and secured return (generateToken)
            $isSecuredReturn = false;
            $contentResponseName = 'labelV2Response';

            if (!empty($request->getIsSecuredReturn())) {
                $isSecuredReturn = true;
                $contentResponseName = 'tokenV2Response';
            }

            // call Api
            [$shipmentDataInfo, $labelBinary, $cn23Binary] = $this->labellingApi->generateLabel($labelGenerationPayload, $isSecuredReturn);

            // parse result
            $parcelNumber = null;

            if ($shipmentDataInfo->$contentResponseName) {
                $parcelNumber = $shipmentDataInfo->$contentResponseName->parcelNumber;
            }

            // store info
            if (empty($parcelNumber)) {
                $result->setErrors($shipmentDataInfo->messages);
            } else {
                $result->setTrackingNumber($parcelNumber);
                $completeLabel = $labelBinary;
                if (!empty($cn23Binary)) {
                    $completeLabel = $this->helperPdf->combineLabelsPdf([$labelBinary, $cn23Binary])->render();
                }
                $result->setShippingLabelContent($completeLabel);
                $result->setCn23Content($cn23Binary);
            }

            if (!is_null($returnLabelGenerationPayload) && $returnLabelGenerationPayload instanceof \LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload) {
                // Set manually the original tracking number needed for CN23 (not saved yet in database so not added when creating return payload)
                $returnLabelGenerationPayload->setOriginalTrackingNumber($parcelNumber);

                //call Api
                [
                    $shipmentDataInfo,
                    $labelBinary,
                    $cn23Binary,
                ] = $this->labellingApi->generateLabel($returnLabelGenerationPayload);

                // parse result
                $parcelNumber = null;
                if ($shipmentDataInfo->$contentResponseName) {
                    $parcelNumber = $shipmentDataInfo->$contentResponseName->parcelNumber;
                }

                //store return label in our custom field
                if (empty($parcelNumber)) {
                    $result->setErrors($shipmentDataInfo->messages);
                } else {
                    $completeLabel = $labelBinary;
                    if (!empty($cn23Binary)) {
                        $completeLabel = $this->helperPdf->combineLabelsPdf([$labelBinary, $cn23Binary])->render();
                    }
                    // Add the tracking to shipment
                    $shipment = $request->getOrderShipment();
                    $carrierTitle = $this->getConfigData('title');
                    $this->returnLabelGenerator->addTrackNumbers($shipment, [$parcelNumber], self::CODE, $carrierTitle);
                    $result->setLpcReturnShippingLabelContent($completeLabel);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Error while generating Label',
                ['request' => $request, 'message' => $e->getMessage()]
            );
            $result->setErrors($e->getMessage());
        }

        $this->changeOrderStatusIfNeeded($request);

        return $result;
    }

    /**
     * @param \Magento\Framework\DataObject $request
     */
    protected function changeOrderStatusIfNeeded(DataObject $request)
    {
        if ($request->getIsReturnLabel()) {
            return;
        }

        $defaultStatusAfterLabelling = $this->helperData->getConfigValue(
            'lpc_advanced/lpc_labels/orderStatusAfterGeneration',
            $request->getStoreId()
        );
        $statusAfterPartialShipping = $this->helperData->getConfigValue(
            'lpc_advanced/lpc_labels/orderStatusAfterPartialExpedition',
            $request->getStoreId()
        );

        if (null === $defaultStatusAfterLabelling && null === $statusAfterPartialShipping) {
            return;
        }

        $order = $request->getOrderShipment()->getOrder();

        $totalQtyOrdered = 0;
        foreach ($order->getAllItems() as $item) {
            if (!$item->getIsVirtual()) {
                $totalQtyOrdered += $item->getQtyOrdered();
            }
        }

        $nbShippedItems = 0;
        foreach ($order->getAllVisibleItems() as $item) {
            $nbShippedItems += $item->getQtyShipped();
        }

        if ($nbShippedItems === $totalQtyOrdered) {
            if (null !== $defaultStatusAfterLabelling) {
                $order->setState(Order::STATE_COMPLETE)
                      ->setStatus($defaultStatusAfterLabelling);
            }
        } elseif (null !== $statusAfterPartialShipping) {
            $order->setStatus($statusAfterPartialShipping);
        }
    }

    /**
     * Map request to shipment
     *
     * @param \Magento\Framework\DataObject $request
     *
     * @return \LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload
     * @throws \Exception
     */
    protected function mapRequestToShipment(DataObject $request)
    {
        $sender = [
            'companyName' => $request['shipper_contact_company_name'],
            'firstName'   => $request['shipper_contact_person_first_name'],
            'lastName'    => $request['shipper_contact_person_last_name'],
            'street'      => $request['shipper_address_street_1'],
            'street2'     => $request['shipper_address_street_2'],
            'city'        => $request['shipper_address_city'],
            'zipCode'     => $request['shipper_address_postal_code'],
            'email'       => $request['shipper_email'],
        ];

        $senderSpecificCountryCode = $this->helperCountryOffer->getLpcCountryCodeSpecificDestination($request['shipper_address_country_code'], $sender['zipCode']);

        $sender['countryCode'] = $senderSpecificCountryCode === false ? $request['shipper_address_country_code'] : $senderSpecificCountryCode;

        $recipient = [
            'companyName'  => $request['recipient_contact_company_name'],
            'firstName'    => $request['recipient_contact_person_first_name'],
            'lastName'     => $request['recipient_contact_person_last_name'],
            'street'       => $request['recipient_address_street_1'],
            'street2'      => $request['recipient_address_street_2'],
            'city'         => $request['recipient_address_city'],
            'zipCode'      => $request['recipient_address_postal_code'],
            'stateCode'    => $request['recipient_address_state_or_province_code'],
            'email'        => $request['recipient_email'],
            'mobileNumber' => $request['recipient_contact_phone_number'],
        ];

        $recipientSpecificCountryCode = $this->helperCountryOffer->getLpcCountryCodeSpecificDestination($request['recipient_address_country_code'], $recipient['zipCode']);
        $recipient['countryCode'] = $recipientSpecificCountryCode === false ? $request['recipient_address_country_code'] : $recipientSpecificCountryCode;

        $originCountryId = $request['shipper_address_country_code'];
        $productCode = $this->helperCountryOffer->getProductCodeFromRequest($request, $originCountryId, false);
        if (empty($productCode)) {
            $this->logger->error('Outward label not allowed for this destination');
            throw new \Exception(__('Outward label not allowed for this destination'));
        }

        $shippingMethodUsed = $request->getShippingMethod();
        $itemsForCn23 = $request->getPackageItems();
        $shipment = $request->getOrderShipment();
        $order = $shipment->getOrder();
        $shippingType = $shipment->getLpcShippingType();


        $postData = $this->requestInterface->getPost();
        if (empty($shippingType) && isset($postData['lpcMultiShipping']['lpc_use_multi_parcels']) && $postData['lpcMultiShipping']['lpc_use_multi_parcels'] === 'on') {
            $parcelsAmount = intval($postData['lpcMultiShipping']['lpc_multi_parcels_amount']);
            $countShipments = count($order->getShipmentsCollection());
            $countShipments ++;

            $shippingType = $countShipments === $parcelsAmount ? GenerateLabelPayload::LABEL_TYPE_MASTER : GenerateLabelPayload::LABEL_TYPE_FOLLOWER;
        }

        if ($shippingType === GenerateLabelPayload::LABEL_TYPE_MASTER) {

            // Retrieve the selected HS Code attribute from the configuration
            $hsCodeAttribute = $this->helperData->getConfigValue(
                'lpc_advanced/lpc_labels/hs_code_attribute',
                $request->getStoreId()
            );
            // Set default attribute if configuration value is empty
            if (!$hsCodeAttribute) {
                $hsCodeAttribute = 'lpc_hs_code';
            }
            $itemsForCn23 = [];

            $orderItems = $order->getAllItems();
            foreach ($orderItems as $item) {
                $itemPrice = $item->getPrice();

                if (empty($itemPrice)) {
                    $parentItem = $item->getParentItem();
                    if (!empty($parentItem)) {
                        continue;
                    }
                }
                // Fetch the HS code using the selected attribute from the product
                $hsCodeValue = $item->getProduct()->getData($hsCodeAttribute);

                $itemsForCn23[] = [
                    'weight'                 => $item->getWeight(),
                    'qty'                    => (int) $item->getQtyOrdered(),
                    'name'                   => $item->getName(),
                    'sku'                    => $item->getSku(),
                    'order_item_id'          => $item->getId(),
                    'customs_value'          => $itemPrice,
                    'row_weight'             => $item->getProduct()->getRowWeight(),
                    'currency'               => $item->getProduct()->getCurrency(),
                    'country_of_manufacture' => $item->getProduct()->getCountryOfManufacture(),
                    'lpc_hs_code'            => $hsCodeValue,
                ];
            }
        }

        $multiShippingData = $this->requestQuery->getParam('lpcMultiShipping');
        $shipmentData = $this->requestQuery->getParam('shipment');

        $shippingInstructions = $order->getLpcShippingNote();
        if (empty($shippingInstructions)) {
            $shippingInstructions = $request->getInstructions();
        }

        $payload = $this->generateLabelPayload->resetPayload()
                                              ->withCredentials($request->getStoreId())
                                              ->withCommercialName(null, $request->getStoreId())
                                              ->withCuserInfoText()
                                              ->withSender($sender, $request->getStoreId())
                                              ->withAddressee($recipient, null, $request->getStoreId(), $shippingMethodUsed)
                                              ->withPreparationDelay($request->getPreparationDelay(), $request->getStoreId())
                                              ->withProductCode($productCode)
                                              ->withOutputFormat($request->getOutputFormat(), $request->getStoreId())
                                              ->withInstructions($shippingInstructions)
                                              ->withOrderNumber($order->getIncrementId())
                                              ->withPackage($request->getPackageParams(), $request->getPackageItems())
                                              ->withCustomsDeclaration(
                                                  $shipment,
                                                  $itemsForCn23,
                                                  $recipient['countryCode'],
                                                  $recipient['zipCode'],
                                                  $request->getStoreId(),
                                                  $originCountryId,
                                                  $shippingType
                                              )
                                              ->withPostalNetwork($recipient['countryCode'], $productCode, $shippingMethodUsed)
                                              ->withDdp(
                                                  $shipment,
                                                  $shippingMethodUsed,
                                                  $recipient
                                              )
                                              ->withMultiShipping($order, $shipment, $multiShippingData, $shipmentData)
                                              ->withBlockingCode($shippingMethodUsed, $itemsForCn23, $order, $shipment, $postData, $request->getStoreId());

        if ($shippingMethodUsed == self::CODE_SHIPPING_METHOD_RELAY) {
            $payload->withPickupLocationId($order->getLpcRelayId());
        }

        $customAmount = null;
        // If creating label when creating shipment in Magento order edition, we get the custom option from POST data
        if (isset($postData['lpcInsurance']['lpc_use_insurance'])) {
            $customAmount = $postData['lpcInsurance']['lpc_insurance_amount'];
            $insuranceParam = 'on' === $postData['lpcInsurance']['lpc_use_insurance'];
        }
        $insuranceConfig = $this->helperData->getConfigValue(
            'lpc_advanced/lpc_labels/isUsingInsurance',
            $request->getStoreId()
        );

        // Use insurance if option checked (when in order edition). In other cases only if option enabled in config
        if ((isset($insuranceParam) && $insuranceParam) || (!isset($insuranceParam) && $insuranceConfig)) {
            $shipment = $shipment;
            $total = 0;
            foreach ($shipment->getAllItems() as $item) {
                $orderItem = $item->getOrderItem();
                if (!empty($orderItem)) {
                    $total += $orderItem->getBaseRowTotal();
                }
            }

            $payload->withInsuranceValue($total, $productCode, $recipient['countryCode'], $shippingMethodUsed, $recipient['zipCode'], $shipment, $originCountryId, $customAmount);
        }

        $registeredMailLevel = $this->helperData->getConfigValue(
            'lpc_advanced/lpc_labels/registeredMailLevel',
            $request->getStoreId()
        );
        if (!empty($registeredMailLevel)) {
            $payload->withRecommendationLevel($registeredMailLevel);
        }

        return $payload;
    }

    /**
     * @param \Magento\Framework\DataObject $request
     *
     * @return mixed
     * @throws \Exception
     */
    protected function mapRequestToReturn(DataObject $request)
    {
        $sender = [
            'firstName' => $request['shipper_contact_person_first_name'],
            'lastName'  => $request['shipper_contact_person_last_name'],
            'street'    => $request['shipper_address_street_1'],
            'street2'   => $request['shipper_address_street_2'],
            'city'      => $request['shipper_address_city'],
            'zipCode'   => $request['shipper_address_postal_code'],
            'email'     => $request['shipper_email'],
        ];

        $senderSpecificCountryCode = $this->helperCountryOffer->getLpcCountryCodeSpecificDestination($request['shipper_address_country_code'], $sender['zipCode']);
        $sender['countryCode'] = $senderSpecificCountryCode === false ? $request['shipper_address_country_code'] : $senderSpecificCountryCode;

        $recipient = [
            'companyName' => $request['recipient_contact_company_name'],
            'firstName'   => $request['recipient_contact_person_first_name'],
            'lastName'    => $request['recipient_contact_person_last_name'],
            'street'      => $request['recipient_address_street_1'],
            'street2'     => $request['recipient_address_street_2'],
            'city'        => $request['recipient_address_city'],
            'zipCode'     => $request['recipient_address_postal_code'],
            'email'       => $request['recipient_email'],
        ];

        $recipientSpecificCountryCode = $this->helperCountryOffer->getLpcCountryCodeSpecificDestination($request['recipient_address_country_code'], $recipient['zipCode']);
        $recipient['countryCode'] = $recipientSpecificCountryCode === false ? $request['recipient_address_country_code'] : $recipientSpecificCountryCode;

        $originCountryId = $request['recipient_address_country_code'];
        $productCode = $this->helperCountryOffer->getProductCodeFromRequest($request, $originCountryId, true);
        if ($productCode === false) {
            $this->logger->error('Inward label not allowed for this destination');
            throw new \Exception(__('Inward label not allowed for this destination'));
        }

        $shippingInstructions = $request->getOrderShipment()->getOrder()->getLpcShippingNote();
        if (empty($shippingInstructions)) {
            $shippingInstructions = $request->getInstructions();
        }

        $shippingMethodUsed = $request->getShippingMethod();

        $payload = $this->generateLabelPayload->resetPayload()
                                              ->isReturnLabel()
                                              ->withCredentials($request->getStoreId())
                                              ->withCommercialName(null, $request->getStoreId())
                                              ->withCuserInfoText()
                                              ->withSender($sender, $request->getStoreId())
                                              ->withAddressee($recipient, null, $request->getStoreId())
                                              ->withPreparationDelay($request->getPreparationDelay(), $request->getStoreId())
                                              ->withProductCode($productCode)
                                              ->withOutputFormat($request->getOutputFormat(), $request->getStoreId(), $productCode)
                                              ->withInstructions($shippingInstructions)
                                              ->withOrderNumber($request->getOrderShipment()->getOrder()->getIncrementId())
                                              ->withPackage($request->getPackageParams(), $request->getPackageItems())
                                              ->withCustomsDeclaration(
                                                  $request->getOrderShipment(),
                                                  $request->getPackageItems(),
                                                  $sender['countryCode'],
                                                  $sender['zipCode'],
                                                  $request->getStoreId(),
                                                  $originCountryId
                                              );

        // Insurance
        $insuranceConfig = $this->helperData->getConfigValue(
            'lpc_advanced/lpc_return_labels/isUsingInsuranceInward',
            $request->getStoreId()
        );
        if ($insuranceConfig) {
            $shipment = $request->getOrderShipment();
            $total = 0;
            foreach ($shipment->getAllItems() as $item) {
                $orderItem = $item->getOrderItem();
                if (!empty($orderItem)) {
                    $total += $orderItem->getBaseRowTotal();
                }
            }

            $payload->withInsuranceValue($total, $productCode, $recipient['countryCode'], $shippingMethodUsed, $recipient['zipCode'], $shipment, $originCountryId);
        }

        return $payload;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     *
     * @return bool|\Magento\Framework\DataObject|\Magento\Shipping\Model\Rate\Result|null
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->isActive()) {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateFactory->create();

        $originCountryId = $request->getCountryId();
        $destCountryId = $request->getDestCountryId();
        $cartWeight = $request->getPackageWeight();
        $cartPrice = $request->getPackageValue();

        $beforeCoupons = $this->helperData->getConfigValue('carriers/lpc_group/price_before_coupons');
        if (!$beforeCoupons) {
            $allItems = $request->getAllItems();
            if (!empty($allItems[0])) {
                $quote = $allItems[0]->getQuote();
                $quote->collectTotals();

                // Get the cart total after coupons are applied
                $cartPrice = $quote->getGrandTotal();
            }
        }

        $freeShipping = $request->getFreeShipping();
        if (empty($cartPrice) && !empty($request->getBaseSubtotalWithDiscountInclTax())) {
            $cartPrice = $request->getBaseSubtotalWithDiscountInclTax();
        }
        $destPostCode = $request->getDestPostcode();

        foreach (self::METHODS_CODES_TRANSLATIONS as $oneMethodCode => $methodName) {
            if ($this->helperData->getConfigValue('carriers/lpc_group/' . $oneMethodCode . '_enable')) {
                $method = $this->getLpcShippingMethod($oneMethodCode, $destCountryId, $destPostCode, $cartPrice, $cartWeight, $originCountryId, $freeShipping);
                if (!empty($method)) {
                    $result->append($method);
                }
            }
        }

        return $result;
    }

    /**
     * Build method if destination and weight fits configuration
     *
     * @param $methodCode
     * @param $destCountryId
     * @param $destPostCode
     * @param $cartPrice
     * @param $cartWeight
     * @param $originCountryId
     * @param $freeShipping
     *
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method|null
     */
    private function getLpcShippingMethod($methodCode, $destCountryId, $destPostCode, $cartPrice, $cartWeight, $originCountryId, $freeShipping)
    {
        // DDP for GB must be commercial and between 160€ and 1050€
        $customsCategory = $this->helperData->getAdvancedConfigValue('lpc_labels/defaultCustomsCategory');
        $isCommercialSend = CustomsCategory::COMMERCIAL_SHIPMENT === intval($customsCategory);
        if (self::CODE_SHIPPING_METHOD_DOMICILE_AS_DDP === $methodCode && 'GB' === $destCountryId && ($cartPrice < 160 || $cartPrice > 1050 || !$isCommercialSend)) {
            return null;
        }

        // Free shipping set for the method
        if ($this->helperData->getConfigValue('carriers/lpc_group/' . $methodCode . '_free')) {
            $method = $this->getMethodStructure($methodCode);
            $method->setPrice(0);

            // Add DDP additional price
            if (in_array($methodCode, [self::CODE_SHIPPING_METHOD_DOMICILE_AS_DDP, self::CODE_SHIPPING_METHOD_EXPERT_DDP]) && in_array($destCountryId, self::COUNTRIES_DDP)) {
                $extraCost = $this->helperData->getAdvancedConfigValue('lpc_ddp/extracost_' . strtolower($destCountryId));
                if (!empty($extraCost)) {
                    $method->setPrice($extraCost);
                }
            }

            return $method;
        }

        // Get available slices for this destination and weight order by prices asc
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $pricesItems = $this->pricesRepository->getList($searchCriteria, 'method', $methodCode)->getItems();

        if (empty($pricesItems)) {
            return null;
        }
        $slices = $this->helperCountryOffer->getSlicesForDestination($methodCode, $destCountryId, $pricesItems, $destPostCode, $cartPrice, $cartWeight, $originCountryId);

        if (empty($slices)) {
            return null;
        }

        $method = $this->getMethodStructure($methodCode);

        $methodPrice = $this->isColissimoPass() ? 0 : $slices[0]->getPrice();
        // Free shipping due to cart rules (coupon)
        if ($freeShipping == 1) {
            $methodPrice = 0;
        }
        $method->setPrice($methodPrice);

        // Handle DDP additional price
        if (in_array($methodCode, [self::CODE_SHIPPING_METHOD_DOMICILE_AS_DDP, self::CODE_SHIPPING_METHOD_EXPERT_DDP]) && in_array($destCountryId, self::COUNTRIES_DDP)) {
            $extraCost = $this->helperData->getAdvancedConfigValue('lpc_ddp/extracost_' . strtolower($destCountryId));
            if (!empty($extraCost)) {
                $newPrice = $methodPrice + $extraCost;
                $method->setPrice($newPrice);
            }
        }

        return $method;
    }

    /**
     * Prepare the base structure of the shipping method (same for all Colissimo methods)
     *
     * @param $methodCode
     *
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    private function getMethodStructure($methodCode)
    {
        $method = $this->_rateMethodFactory->create();
        $method->setCarrier(self::CODE);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($methodCode);
        $name = $this->helperData->getConfigValue('carriers/lpc_group/' . $methodCode . '_label');
        $method->setMethodTitle(!empty($name) ? $name : 'colissimo');

        if (strpos($methodCode, 'ddp') !== false && !empty($this->helperData->getAdvancedConfigValue('lpc_ddp/ddp_description'))) {
            $method->setColissimoDescription($this->helperData->getAdvancedConfigValue('lpc_ddp/ddp_description'));
        }

        return $method;
    }


    /**
     * Do request to shipment
     *
     * @param Request $request
     *
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function requestToShipment($request)
    {
        $packages = $request->getPackages();
        if (!is_array($packages) || !$packages) {
            throw new \Magento\Framework\Exception\LocalizedException(__('No packages for request'));
        }
        if ($request->getStoreId() != null) {
            $this->setStore($request->getStoreId());
        }
        $data = [];
        foreach ($packages as $packageId => $package) {
            $request->setPackageId($packageId);
            $request->setPackagingType($package['params']['container']);
            $request->setPackageWeight($package['params']['weight']);
            $request->setPackageParams(new \Magento\Framework\DataObject($package['params']));
            $request->setPackageItems($package['items']);
            $result = $this->_doShipmentRequest($request);

            if ($result->hasErrors()) {
                $this->rollBack($data);
                break;
            } else {
                $labelContent = $result->getShippingLabelContent();
                $cn23Content = $result->getCn23Content();
                if (!empty($cn23Content)) {
                    $this->setCn23FlagForShipment($request->getOrderShipment());
                }

                $data[] = [
                    'tracking_number' => $result->getTrackingNumber(),
                    'label_content'   => $labelContent,
                ];

                // Save return label if generated simultaneously
                $returnLabelContent = $result->getLpcReturnShippingLabelContent();
                if (!empty($returnLabelContent)) {
                    $request->getOrderShipment()->setLpcReturnLabel($returnLabelContent);
                }
            }
            if (!isset($isFirstRequest)) {
                $request->setMasterTrackingId($result->getTrackingNumber());
                $isFirstRequest = false;
            }
        }

        $response = new DataObject(['info' => $data]);
        if ($result->getErrors()) {
            $response->setErrors($result->getErrors());
        }

        return $response;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     *
     * @throws \Exception
     */
    public function setCn23FlagForShipment($shipment)
    {
        $shipment->setDataUsingMethod(
            'lpc_label_cn_23',
            true
        );

        $shipment->save();
    }

    private function isColissimoPass()
    {
        if ($this->helperData->isModuleOutputEnabled('Quadra_Colissimopass')) {
            $colissimoPassModelUser = $this->objectManager->create(\Quadra\Colissimopass\Model\User::class);
            if ($this->customerSession->isLoggedIn()) {
                return $colissimoPassModelUser->checkIsLog() && $colissimoPassModelUser->checkIsActive();
            } else {
                $colissimoPassSession = $this->checkoutSession->getData('colissimopass_contract');

                return $colissimoPassSession['isLog'] == 1 && $colissimoPassSession['status'] == "ACTIVE";
            }
        }

        return false;
    }
}
