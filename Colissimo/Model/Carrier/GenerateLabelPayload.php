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

use LaPoste\Colissimo\Helper\CountryOffer;
use LaPoste\Colissimo\Model\Config\Source\CustomsCategory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Message\ManagerInterface;
use LaPoste\Colissimo\Model\AccountApi;

class GenerateLabelPayload implements \LaPoste\Colissimo\Api\Carrier\GenerateLabelPayload
{
    const MAX_INSURANCE_AMOUNT = 5000;
    const MAX_INSURANCE_AMOUNT_RELAY = 1000;
    const FORCED_ORIGINAL_IDENT = 'A';
    const RETURN_LABEL_LETTER_MARK = 'R';
    const RETURN_TYPE_CHOICE_NO_RETURN = 3;

    const US_COUNTRY_CODE = 'US';
    const COUNTRIES_NEEDING_STATE = ['CA', self::US_COUNTRY_CODE];

    const LABEL_TYPE_CLASSIC = 'CLASSIC';
    const LABEL_TYPE_MASTER = 'MASTER';
    const LABEL_TYPE_FOLLOWER = 'FOLLOWER';
    const DEFAULT_FORMAT = 'PDF_A4_300dpi';

    protected $payload;

    protected $printFormats;

    protected $registeredMailLevel;

    protected $helperData;

    protected $logger;

    protected $isReturnLabel;

    protected $orderItemRepository;

    protected $invoiceService;

    protected $transaction;

    protected $countryOfferHelper;

    protected $productMetadata;

    protected $productRepository;

    protected $eoriAdded;

    protected $articleDescriptions;

    protected $messageManager;

    private AccountApi $accountApi;

    public function __construct(
        \LaPoste\Colissimo\Model\Config\Source\PrintFormats $printFormats,
        \LaPoste\Colissimo\Model\Config\Source\RegisteredMailLevel $registeredMailLevel,
        \LaPoste\Colissimo\Helper\Data $helperData,
        \LaPoste\Colissimo\Logger\Colissimo $logger,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \LaPoste\Colissimo\Helper\CountryOffer $countryOfferHelper,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        AccountApi $accountApi
    ) {
        $this->payload = [
            'letter' => [
                'service' => [],
                'parcel'  => [],
            ],
        ];

        $this->printFormats = $printFormats;
        $this->registeredMailLevel = $registeredMailLevel;
        $this->helperData = $helperData;
        $this->logger = $logger;
        $this->isReturnLabel = false;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->orderItemRepository = $orderItemRepository;
        $this->countryOfferHelper = $countryOfferHelper;
        $this->productMetadata = $productMetadata;
        $this->productRepository = $productRepository;
        $this->messageManager = $messageManager;
        $this->accountApi = $accountApi;

        $this->eoriAdded = false;
    }

    public function withSender(array $sender = null, $storeId = null)
    {
        if (null === $sender) {
            $sender = [
                'companyName' => $this->helperData->getConfigValue(
                    'general/store_information/name',
                    $storeId
                ),
                'street'      => $this->helperData->getConfigValue(
                    'general/store_information/street_line1',
                    $storeId
                ),
                'street2'     => $this->helperData->getConfigValue(
                    'general/store_information/street_line2',
                    $storeId
                ),
                'countryCode' => $this->helperData->getConfigValue(
                    'general/store_information/country_id',
                    $storeId
                ),
                'city'        => $this->helperData->getConfigValue(
                    'general/store_information/city',
                    $storeId
                ),
                'zipCode'     => $this->helperData->getConfigValue(
                    'general/store_information/postcode',
                    $storeId
                ),
                'email'       => $this->helperData->getConfigValue(
                    'sales_email/shipment_comment/identity',
                    $storeId
                ),
            ];
        }

        $this->payload['letter']['sender'] = [
            'address' => [
                'companyName' => $sender['companyName'] ?? '',
                'firstName'   => $sender['firstName'] ?? '',
                'lastName'    => $sender['lastName'] ?? '',
                'line2'       => $sender['street'] ?? '',
                'countryCode' => $sender['countryCode'],
                'city'        => $sender['city'],
                'zipCode'     => $sender['zipCode'],
                'email'       => $sender['email'] ?? '',
            ],
        ];

        if (!empty($sender['street2'])) {
            $this->payload['letter']['sender']['address']['line3'] = $sender['street2'];
        }

        $payloadCountryCode = $this->payload['letter']['sender']['address']['countryCode'];

        $this->payload['letter']['sender']['address']['countryCode'] = $this->countryOfferHelper->getMagentoCountryCodeFromSpecificDestination($payloadCountryCode) === false
            ? $payloadCountryCode
            : $this->countryOfferHelper->getMagentoCountryCodeFromSpecificDestination($payloadCountryCode);

        $zipDashPos = strpos($sender['zipCode'], '-');
        if (self::US_COUNTRY_CODE === $payloadCountryCode && $zipDashPos !== false) {
            $this->payload['letter']['sender']['address']['zipCode'] = substr($sender['zipCode'], 0, $zipDashPos);
        }

        return $this;
    }

    public function withCommercialName($commercialName = null, $storeId = null)
    {
        if (null === $commercialName) {
            $commercialName = $this->helperData->getConfigValue(
                'general/store_information/name',
                $storeId
            );
        }


        if (empty($commercialName)) {
            unset($this->payload['letter']['service']['commercialName']);
        } else {
            $this->payload['letter']['service']['commercialName'] = $commercialName;
        }

        return $this;
    }

    public function withCredentials($storeId = null)
    {
        if ('api' !== $this->helperData->getAdvancedConfigValue('lpc_general/connectionMode', $storeId)) {
            $contractNumber = $this->helperData->getAdvancedConfigValue('lpc_general/id_webservices', $storeId);
            if (!empty($contractNumber)) {
                $this->payload['contractNumber'] = $contractNumber;
            }
            $password = $this->helperData->getAdvancedConfigValue('lpc_general/pwd_webservices', $storeId);
            if (!empty($password)) {
                $this->payload['password'] = $password;
            }
        }

        $parentAccountId = $this->helperData->getAdvancedConfigValue('lpc_general/parent_id_webservices', $storeId);
        if (!empty($parentAccountId)) {
            $this->payload['fields']['field'][] = [
                'key'   => 'ACCOUNT_NUMBER',
                'value' => $parentAccountId,
            ];
        }

        return $this;
    }

    public function withAddressee(array $addressee, $orderRef = null, $storeId = null, $shippingMethodUsed = '')
    {
        $this->payload['letter']['addressee'] = [
            'address' => [
                'companyName' => $addressee['companyName'] ?? '',
                'firstName'   => $addressee['firstName'] ?? '',
                'lastName'    => $addressee['lastName'] ?? '',
                'line2'       => $addressee['street'],
                'countryCode' => $addressee['countryCode'],
                'city'        => $addressee['city'],
                'zipCode'     => $addressee['zipCode'],
                'email'       => $addressee['email'] ?? '',
            ],
        ];

        if (!empty($addressee['street2'])) {
            $this->payload['letter']['addressee']['address']['line3'] = $addressee['street2'];
        }

        if ($this->isReturnLabel) {
            if ($this->helperData->getConfigValue(
                'lpc_advanced/lpc_return_labels/showServiceInformation',
                $storeId
            )) {
                $this->payload['letter']['addressee']['serviceInfo'] =
                    $this->helperData->getConfigValue(
                        'lpc_advanced/lpc_return_labels/serviceInformation',
                        $storeId
                    );
            }

            if ($this->helperData->getConfigValue(
                'lpc_advanced/lpc_return_labels/showOrderRef',
                $storeId
            )) {
                if (!empty($orderRef)) {
                    $this->payload['letter']['addressee']['codeBarForReference'] = "true";
                    $this->payload['letter']['addressee']['addresseeParcelRef'] = $orderRef;
                } else {
                    $this->logger->error(
                        'Unknown orderRef',
                        ['given' => $orderRef]
                    );
                }
            }

            $this->payload['letter']['addressee']['address']['mobileNumber'] = $addressee['mobileNumber'] ?? '';
        } else {
            if (Colissimo::CODE_SHIPPING_METHOD_RELAY === $shippingMethodUsed) {
                $this->payload['letter']['addressee']['address']['mobileNumber'] = $addressee['mobileNumber'] ?? '';
            } else {
                $this->payload['letter']['addressee']['address']['phoneNumber'] = $addressee['mobileNumber'] ?? '';
            }
        }

        $payloadCountryCode = $this->payload['letter']['addressee']['address']['countryCode'];

        $this->payload['letter']['addressee']['address']['countryCode'] = $this->countryOfferHelper->getMagentoCountryCodeFromSpecificDestination($payloadCountryCode) === false
            ? $payloadCountryCode
            : $this->countryOfferHelper->getMagentoCountryCodeFromSpecificDestination($payloadCountryCode);

        if (in_array($payloadCountryCode, self::COUNTRIES_NEEDING_STATE) && !empty($addressee['stateCode'])) {
            $this->payload['letter']['addressee']['address']['stateOrProvinceCode'] = $addressee['stateCode'];
        }

        $zipDashPos = strpos($addressee['zipCode'], '-');
        if (self::US_COUNTRY_CODE === $payloadCountryCode && $zipDashPos !== false) {
            $this->payload['letter']['addressee']['address']['zipCode'] = substr($addressee['zipCode'], 0, $zipDashPos);
        }

        return $this;
    }

    public function withPickupLocationId($pickupLocationId)
    {
        if (null === $pickupLocationId) {
            unset($this->payload['letter']['parcel']['pickupLocationId']);
        } else {
            $this->payload['letter']['parcel']['pickupLocationId'] = $pickupLocationId;
        }

        return $this;
    }


    public function withProductCode($productCode)
    {
        if (!in_array($productCode, Colissimo::ALL_PRODUCT_CODES, true)) {
            $this->logger->error(
                'Unknown productCode',
                [
                    'given' => $productCode,
                    'known' => Colissimo::ALL_PRODUCT_CODES,
                ]
            );
            throw new \Exception('Unknown Product code!');
        }

        $this->payload['letter']['service']['productCode'] = $productCode;

        $this->payload['letter']['service']['returnTypeChoice'] = self::RETURN_TYPE_CHOICE_NO_RETURN;

        return $this;
    }

    public function withFtd($destinationCountryId, $storeId = null)
    {
        $originCountryId = $this->helperData->getConfigValue(
            'shipping/origin/country_id',
            $storeId
        );

        if ($this->helperData->getAdvancedConfigValue('lpc_labels/isFtd', $storeId)
            && !in_array(strtoupper($originCountryId), CountryOffer::DOM1_COUNTRIES_CODE)
            && in_array($destinationCountryId, CountryOffer::COUNTRIES_FTD)) {
            $this->payload['letter']['parcel']['ftd'] = true;
        } else {
            unset($this->payload['letter']['parcel']['ftd']);
        }

        return $this;
    }

    public function withDepositDate(\DateTime $depositDate)
    {
        $now = new \DateTime();
        if ($depositDate->getTimestamp() < $now->getTimestamp()) {
            $this->logger->warning(
                'Given DepositDate is in the past, using today instead.',
                ['given' => $depositDate, 'now' => $now]
            );
            $depositDate = $now;
        }

        $this->payload['letter']['service']['depositDate'] = $depositDate->format('Y-m-d');

        return $this;
    }

    public function withPreparationDelay($delay = null, $storeId = null)
    {
        if (null === $delay) {
            $delay = $this->helperData->getAdvancedConfigValue(
                'lpc_labels/averagePreparationDelay',
                $storeId
            );
        }

        $depositDate = new \DateTime();

        $delay = (int) $delay;
        if ($delay > 0) {
            $depositDate->add(new \DateInterval("P{$delay}D"));
        } else {
            $this->logger->warning(
                'Preparation delay was not applied because it was negative or zero!',
                ['given' => $delay]
            );
        }

        return $this->withDepositDate($depositDate);
    }

    public function withOutputFormat($outputFormat = null, $storeId = null, $productCode = null)
    {
        if (null === $outputFormat) {
            $outputFormat = $this->helperData->getAdvancedConfigValue(
                'lpc_labels/outwardPrintFormat',
                $storeId
            );
        }

        if ($this->getIsReturnLabel() && Colissimo::PRODUCT_CODE_RETURN_INT === $productCode) {
            $outputFormat = self::DEFAULT_FORMAT;
        }

        $allowedPrintFormats = array_map(
            function ($v) {
                return $v['value'];
            },
            $this->printFormats->toOptionArray()
        );

        if (!in_array($outputFormat, $allowedPrintFormats)) {
            $this->logger->error(
                'Unknown outputFormat',
                ['given' => $outputFormat, 'known' => $allowedPrintFormats]
            );
            throw new \Magento\Framework\Exception\LocalizedException(__('Bad output format'));
        }

        $this->payload['outputFormat'] = [
            'x'                  => 0,
            'y'                  => 0,
            'outputPrintingType' => $outputFormat,
        ];

        return $this;
    }

    public function withOrderNumber($orderNumber)
    {
        $this->payload['letter']['service']['orderNumber'] = $orderNumber;
        $this->payload['letter']['sender']['senderParcelRef'] = $orderNumber;

        return $this;
    }

    public function withPackage(
        \Magento\Framework\DataObject $package,
        array $items
    ) {
        $weightUnit = $package['weight_units'];

        if (empty($package['weight'])) {
            $totalWeight = 0;

            foreach ($items as $piece) {
                if (!empty($piece['row_weight'])) {
                    $weight = (double) $piece['row_weight'];
                } else {
                    $weight = (double) $piece['weight'] * $piece['qty'];
                }
                if ($weight < 0) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Weight cannot be negative!')
                    );
                }

                $fromWeight = number_format($weight, 2, '.', '');
                $totalWeight += $this->helperData->convertWeightToKilogram($fromWeight, $weightUnit);
            }
        } else {
            $fromWeight = number_format($package['weight'], 2, '.', '');
            $totalWeight = $this->helperData->convertWeightToKilogram($fromWeight, $weightUnit);
        }


        if ($totalWeight < 0.01) {
            $totalWeight = 0.01;
        }

        $totalWeight = number_format($totalWeight, 2);

        $this->payload['letter']['parcel']['weight'] = $totalWeight;

        return $this;
    }

    public function withCustomsDeclaration(
        \Magento\Sales\Model\Order\Shipment $shipment,
        array $items,
        $destinationCountryId,
        $destinationPostcode,
        $storeId = null,
        $originCountryId = 'fr',
        $shippingType = null
    ) {
        if (empty($shippingType)) {
            $shippingType = self::LABEL_TYPE_CLASSIC;
        }
        if (!$this->helperData->getAdvancedConfigValue('lpc_labels/isUsingCustomsDeclarations', $storeId) || $shippingType === self::LABEL_TYPE_FOLLOWER) {
            return $this;
        }

        $order = $shipment->getOrder();

        $invoiceCollection = $order->getInvoiceCollection();
        $invoiceCollectionCount = $invoiceCollection->count();

        // Check there is an invoice
        if ($invoiceCollectionCount == 0) {
            // customs declaration needs some invoice information
            $this->logger->error(__METHOD__ . ' : ' . __('Invoice missing for order #%1 to create label', $order->getIncrementId()));
            throw new \Exception(__('Invoice missing for order #%1 to create label', $order->getIncrementId()));
        }

        // No need details if no CN23 required
        if (!$this->countryOfferHelper->getIsCn23RequiredForDestination($destinationCountryId, $destinationPostcode, $originCountryId, $this->isReturnLabel)) {
            return $this;
        }

        // If CN23 and return label, we can only manage if we have one invoice only for the order
        if ($this->isReturnLabel && $invoiceCollectionCount > 1) {
            $this->logger->error(__METHOD__ . ' : ' . __('There must be only one invoice on order #%1 to create return label with CN23.', $order->getIncrementId()));
            throw new \Exception(__('There must be only one invoice on order #%1 to create return label with CN23.', $order->getIncrementId()));
        }

        $invoice = $invoiceCollection->getLastItem();

        $defaultHsCode = $this->helperData->getAdvancedConfigValue(
            'lpc_labels/defaultHsCode',
            $storeId
        );

        $customsArticles = [];
        $this->articleDescriptions = [];

        foreach ($items as $piece) {
            if (empty($piece['currency']) || empty($piece['weight']) || empty($piece['qty']) || empty($piece['customs_value'])) {
                // this happens when packages have been created by main magento process
                $piece = $this->rebuildPiece($piece, $storeId);
            }

            $fromWeight = number_format($piece['weight'], 2, '.', '');
            $pieceWeightInKG = $this->helperData->convertWeightToKilogram($fromWeight, null, $storeId);

            $description = substr($piece['name'], 0, 64);
            $this->articleDescriptions[] = $description;

            $customsArticle = [
                'description'   => $description,
                'quantity'      => $piece['qty'],
                'weight'        => $pieceWeightInKG, // unitary value
                'value'         => (int) $piece['customs_value'], // unitary value
                'currency'      => $piece['currency'],
                'artref'        => substr($piece['sku'], 0, 44),
                'originalIdent' => self::FORCED_ORIGINAL_IDENT,
                'originCountry' => $piece['country_of_manufacture'],
                'hsCode'        => $piece['lpc_hs_code'],
            ];

            // Set specific HS code if defined on the product
            if (empty($customsArticle['hsCode'])) {
                $customsArticle['hsCode'] = $defaultHsCode;
            }

            $customsArticles[] = $customsArticle;
        }

        $numberOfCopies = $this->helperData->getAdvancedConfigValue(
            'lpc_labels/cn23Number',
            $storeId
        );

        $this->payload['letter']['customsDeclarations'] = [
            'includeCustomsDeclarations' => 1,
            'numberOfCopies'             => empty($numberOfCopies) ? 4 : $numberOfCopies,
            'contents'                   => [
                'article' => $customsArticles,
            ],
            'invoiceNumber'              => $invoice->getIncrementId(),
        ];

        $transportationAmount = $order->getShippingAmount();
        if (empty($transportationAmount)) {
            // The Colissimo API rejects labels with a free shipping for the CN23
            $this->logger->error(__METHOD__ . ' : ' . __('The shipping costs must not be free for the customs declaration to be valid.'));
            throw new \Exception(__('The shipping costs must not be free for the customs declaration to be valid.'));
        }

        // payload want centi-currency for these fields.
        $this->payload['letter']['service']['totalAmount'] = (int) ($transportationAmount * 100);
        $this->payload['letter']['service']['transportationAmount'] = (int) ($transportationAmount * 100);

        $customsCategory = $this->helperData->getAdvancedConfigValue(
            'lpc_labels/defaultCustomsCategory',
            $storeId
        );
        if (empty($customsCategory)) {
            $customsCategory = CustomsCategory::OTHER;
        }
        if ($this->isReturnLabel) {
            $customsCategory = CustomsCategory::RETURN_OF_ARTICLES;
        }

        $this->payload['letter']['customsDeclarations']['contents']['category'] = [
            'value' => $customsCategory,
        ];

        if ('GB' === $destinationCountryId && !$this->isReturnLabel) {
            $vatNumber = $this->helperData->getConfigValue('general/store_information/merchant_vat_number', $storeId);

            if (0 === $vatNumber) {
                $this->logger->warning('No VAT number set in config');
            } else {
                $this->payload['letter']['customsDeclarations']['comments'] = 'N. TVA : ' . $vatNumber;
            }
        }

        if ($this->isReturnLabel) {
            $originalInvoiceDate = \DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $invoice->getCreatedAt()
            )->format('Y-m-d');

            $originalParcelNumber = $this->getOriginalParcelNumberFromInvoice($invoice);

            $this->payload['letter']['customsDeclarations']['contents']['original'] = [
                [
                    'originalIdent'         => self::FORCED_ORIGINAL_IDENT,
                    'originalInvoiceNumber' => $invoice->getIncrementId(),
                    'originalInvoiceDate'   => $originalInvoiceDate,
                    'originalParcelNumber'  => $originalParcelNumber,
                ],
            ];
        }

        if ('GB' === $destinationCountryId) {
            $eoriNumber = $this->helperData->getAdvancedConfigValue('lpc_labels/eoriUkNumber', $storeId);

            if ($order->getTotalInvoiced() >= 1000) {
                $eoriNumber .= ' ' . $this->helperData->getAdvancedConfigValue('lpc_labels/eoriNumber', $storeId);
            }
        } else {
            $eoriNumber = $this->helperData->getAdvancedConfigValue('lpc_labels/eoriNumber', $storeId);
        }

        $eoriField = [
            'key'   => 'EORI',
            'value' => $eoriNumber,
        ];
        $this->eoriAdded = true;

        if (!isset($this->payload['fields'])) {
            $this->payload['fields'] = [];
        }

        if (!isset($this->payload['fields']['field'])) {
            $this->payload['fields']['field'] = [];
        }

        $this->payload['fields']['field'][] = $eoriField;

        // CN23 print format
        $cn23PrintFormat = $this->helperData->getAdvancedConfigValue('lpc_labels/cn23PrintFormat', $storeId);
        if (empty($cn23PrintFormat)) {
            $cn23PrintFormat = self::DEFAULT_FORMAT;
        }
        $this->payload['fields']['field'][] = [
            'key'   => 'OUTPUT_PRINT_TYPE_CN23',
            'value' => $cn23PrintFormat,
        ];

        return $this;
    }

    public function withDdp($shipment, $shippingMethod, $recipient)
    {
        if (!in_array($shippingMethod, [Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS_DDP, Colissimo::CODE_SHIPPING_METHOD_EXPERT_DDP])) {
            $this->payload['letter']['parcel']['ddp'] = 'false';

            return $this;
        }

        $description = $shipment->getLpcDdpDescription();
        if (empty($description)) {
            $description = implode(', ', $this->articleDescriptions);
        }
        $this->payload['letter']['customsDeclarations']['description'] = substr($description, 0, 64);

        // Must have the state code for US and CA
        $address = $this->payload['letter']['addressee']['address'];
        if (in_array($address['countryCode'], self::COUNTRIES_NEEDING_STATE) && empty($address['stateOrProvinceCode'])) {
            $this->logger->error('Shipping state missing for DDP label generation', ['shippingMethod' => $shippingMethod]);
            throw new \Magento\Framework\Exception\LocalizedException(__('Shipping state missing for label generation with this country'));
        }

        // Must have a phone number
        if (empty($address['phoneNumber']) && empty($address['mobileNumber'])) {
            $this->logger->error('Phone number missing for DDP label generation', ['shippingMethod' => $shippingMethod]);
            throw new \Magento\Framework\Exception\LocalizedException(__('Please define a mobile phone number for SMS notification tracking'));
        }

        // Must have dimensions
        $lenght = $shipment->getLpcDdpLength();
        $width = $shipment->getLpcDdpWidth();
        $height = $shipment->getLpcDdpHeight();
        if (empty($lenght) || empty($width) || empty($height)) {
            $this->logger->error('Package dimensions missing for DDP label generation', ['shippingMethod' => $shippingMethod]);
            throw new \Magento\Framework\Exception\LocalizedException(__('Please enter the package dimensions'));
        }
        $dimensions = [(int) $lenght, (int) $width, (int) $height];
        sort($dimensions);
        $this->payload['fields']['field'][] = [
            'key'   => 'LENGTH',
            'value' => array_pop($dimensions),
        ];
        $this->payload['fields']['field'][] = [
            'key'   => 'WIDTH',
            'value' => array_pop($dimensions),
        ];
        $this->payload['fields']['field'][] = [
            'key'   => 'HEIGHT',
            'value' => array_pop($dimensions),
        ];

        // Must have EORI
        if (!$this->eoriAdded) {
            $this->logger->error('EORI missing for DDP label generation', ['shippingMethod' => $shippingMethod]);
            throw new \Magento\Framework\Exception\LocalizedException(__('Please enter the EORI code in the Colissimo configuration'));
        }

        $this->payload['letter']['parcel']['ddp'] = 'true';

        return $this;
    }

    protected function getOriginalParcelNumberFromInvoice(
        \Magento\Sales\Api\Data\InvoiceInterface $invoice
    ) {
        $order = $invoice->getOrder();
        $tracksCollection = $order->getTracksCollection()
                                  ->setOrder('created_at', 'desc');

        // find the last non-return track number for this order
        foreach ($tracksCollection as $track) {
            $trackNumber = $track->getTrackNumber();

            if (self::RETURN_LABEL_LETTER_MARK !== substr($trackNumber, 1, 1)) {
                return $trackNumber;
            }
        }
    }

    public function withInsuranceValue($amount, $productCode, $countryCode, $shippingMethodUsed, $destinationPostcode, $shipment, $originCountryId = 'fr', $customAmount = null)
    {
        if (!empty($this->payload['letter']['parcel']['recommendationLevel'])) {
            $this->logger->error(
                'RecommendationLevel and InsuranceValue are mutually incompatible.',
                ['wanting' => 'insuranceValue', 'alreadyGiven' => 'recommendationLevel']
            );
            throw new \Magento\Framework\Exception\LocalizedException(
                __('RecommendationLevel and InsuranceValue are mutually incompatible.')
            );
        }

        $amount = (double) $amount;
        // No amount value directly from POST so check amount value saved on the shipment
        if (empty($customAmount) && !empty($shipment->getLpcInsuranceAmount())) {
            $customAmount = $shipment->getLpcInsuranceAmount();
        }

        if (!empty($customAmount)) {
            $amount = (double) $customAmount;
        }

        if (!in_array($productCode, Colissimo::PRODUCT_CODE_INSURANCE_AVAILABLE)) {
            $this->messageManager->addNoticeMessage(__('Insurance is not available for this country and/or shipping method'));

            return $this;
        }

        // Insurance is set to yes, check if available for this country
        if (!$this->countryOfferHelper->getInsuranceAvailableForDestination($countryCode, $destinationPostcode, $originCountryId)) {
            $this->messageManager->addNoticeMessage(__('Insurance is not available for this country and/or shipping method'));

            return $this;
        }

        $maxInsuranceAmount = $this->getMaxInsuranceAmountByProductCode($productCode);
        if ($amount > $maxInsuranceAmount) {
            $this->logger->warning(
                'Given insurance value amount is too big, forced to ' . $maxInsuranceAmount,
                ['given' => $amount, 'max' => $maxInsuranceAmount]
            );
            $amount = $maxInsuranceAmount;

            $this->messageManager->addNoticeMessage(
                sprintf(
                    __('The insurance value has been lowered to the maximum value authorized for this sending method: %s'),
                    $maxInsuranceAmount
                )
            );
        }

        if ($amount > 0) {
            // payload want centi-euros for this field.
            $this->payload['letter']['parcel']['insuranceValue'] = (int) ($amount * 100);
        } else {
            $this->logger->warning(
                'Insurance value was not applied because it was negative or zero!',
                ['given' => $amount]
            );
        }

        return $this;
    }

    protected function getMaxInsuranceAmountByProductCode($productCode)
    {
        if (!in_array($productCode, Colissimo::PRODUCT_CODE_INSURANCE_AVAILABLE)) {
            return false;
        }

        return Colissimo::PRODUCT_CODE_RELAY === $productCode ? self::MAX_INSURANCE_AMOUNT_RELAY : self::MAX_INSURANCE_AMOUNT;
    }

    public function withRecommendationLevel($recommendation)
    {
        $allowedRegisteredMailLevel = $this->registeredMailLevel->toArray();
        unset($allowedRegisteredMailLevel[null]);
        $allowedRegisteredMailLevel = array_keys($allowedRegisteredMailLevel);

        if (!in_array($recommendation, $allowedRegisteredMailLevel)) {
            $this->logger->error(
                'Unknown recommendation level',
                ['given' => $recommendation, 'known' => $allowedRegisteredMailLevel]
            );
            throw new \Magento\Framework\Exception\LocalizedException(__('Bad recommendation level'));
        }

        if (!empty($this->payload['letter']['parcel']['insuranceValue'])) {
            $this->logger->error(
                'RecommendationLevel and InsuranceValue are mutually incompatible.',
                ['wanting' => 'recommendationLevel', 'alreadyGiven' => 'insuranceValue']
            );
            throw new \Magento\Framework\Exception\LocalizedException(
                __('RecommendationLevel and InsuranceValue are mutually incompatible.')
            );
        }

        $this->payload['letter']['parcel']['recommendationLevel'] = $recommendation;

        return $this;
    }

    public function withCODAmount(string $productCode, array $items, $storeId = null)
    {
        $cashOnDelivery = $this->helperData->getAdvancedConfigValue('lpc_labels/isUsingCashOnDelivery', $storeId);
        if (empty($cashOnDelivery) || !in_array($productCode, [Colissimo::PRODUCT_CODE_WITH_SIGNATURE, Colissimo::PRODUCT_CODE_WITH_SIGNATURE_INTRA_DOM])) {
            return $this;
        }

        $amount = 0;

        foreach ($items as $piece) {
            if (empty($piece['qty']) || empty($piece['customs_value'])) {
                // this happens when packages have been created by main magento process
                $piece = $this->rebuildPiece($piece, $storeId);
            }

            $amount += (double) $piece['customs_value'] * (double) $piece['qty'];
        }

        if ($amount > 0) {
            $this->payload['letter']['parcel']['COD'] = true;
            // payload want centi-euros for this field.
            $this->payload['letter']['parcel']['CODAmount'] = (int) ($amount * 100);
        } else {
            $this->logger->warning(
                'CODAmount was not applied because it was negative or zero!',
                ['given' => $amount]
            );
        }

        return $this;
    }


    public function withReturnReceipt($value = true)
    {
        if ($value) {
            $this->payload['letter']['parcel']['returnReceipt'] = true;
        } else {
            unset($this->payload['letter']['parcel']['returnReceipt']);
        }

        return $this;
    }

    public function withInstructions($instructions)
    {
        if (empty($instructions)) {
            unset($this->payload['letter']['parcel']['instructions']);
        } else {
            $this->payload['letter']['parcel']['instructions'] = $instructions;
        }

        return $this;
    }


    public function withCuserInfoText($info = null)
    {
        if (null === $info) {
            $info = $this->helperData->getCuserInfoText();
        }

        $customField = [
            "key"   => "CUSER_INFO_TEXT",
            "value" => $info,
        ];

        if (!isset($this->payload['fields'])) {
            $this->payload['fields'] = [];
        }

        if (!isset($this->payload['fields']['field'])) {
            $this->payload['fields']['field'] = [];
        }

        $moduleVersion = $this->helperData->getModuleVersion();
        $this->payload['fields']['field'][] = $customField;
        $this->payload['fields']['field'][] = [
            'key'   => 'CUSER_INFO_TEXT_3',
            'value' => 'MAGENTO2;' . $moduleVersion,
        ];

        return $this;
    }

    public function withBlockingCode($shippingMethodUsed, $items, $order, $shipment, $postData, $storeId)
    {
        // Blocking code only available for shipping with signature
        if (!in_array($shippingMethodUsed, [Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS, Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS_DDP])) {
            return $this;
        }

        // Blocking code is not available on the account
        $accountInformation = $this->accountApi->getAccountInformation();
        if (empty($accountInformation['statutCodeBloquant'])) {
            return $this;
        }

        // If the label is generated from the shipment creation/edition page, take the shown option into account
        if (!empty($postData['lpcBlockCode']['lpc_block_code'])) {
            if ('disabled' === $postData['lpcBlockCode']['lpc_block_code']) {
                $this->payload['letter']['parcel']['disabledDeliveryBlockingCode'] = '1';
            }

            return $this;
        }

        $minimumOrderValue = $this->helperData->getAdvancedConfigValue('lpc_shipping/domicileas_block_code_min', $storeId);
        $maximumOrderValue = $this->helperData->getAdvancedConfigValue('lpc_shipping/domicileas_block_code_max', $storeId);

        $orderValue = 0;
        foreach ($items as $piece) {
            if (empty($piece['currency'])) {
                // this happens when packages have been created by main magento process
                $piece = $this->rebuildPiece($piece, $storeId);
            }

            $orderValue += $piece['qty'] * floatval($piece['customs_value']);
        }

        // Follow the general options
        if (!empty($minimumOrderValue) && $orderValue < $minimumOrderValue) {
            $this->payload['letter']['parcel']['disabledDeliveryBlockingCode'] = '1';
        } elseif (!empty($maximumOrderValue) && $orderValue > $maximumOrderValue) {
            $this->payload['letter']['parcel']['disabledDeliveryBlockingCode'] = '1';
        }

        return $this;
    }

    public function isReturnLabel($isReturnLabel = true)
    {
        $this->isReturnLabel = $isReturnLabel;

        return $this;
    }

    public function getIsReturnLabel()
    {
        return $this->isReturnLabel;
    }

    public function checkConsistency()
    {
        $this->checkPickupLocationId();
        $this->checkCommercialName();
        $this->checkSenderAddress();
        $this->checkAddresseeAddress();

        return $this;
    }

    public function assemble()
    {
        return array_merge($this->payload); // makes a copy
    }


    protected function checkPickupLocationId()
    {
        if (Colissimo::PRODUCT_CODE_RELAY === $this->payload['letter']['service']['productCode']
            && (
                !isset($this->payload['letter']['parcel']['pickupLocationId'])
                ||
                empty($this->payload['letter']['parcel']['pickupLocationId'])
            )) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The ProductCode used requires that a pickupLocationId is set!')
            );
        }

        if (Colissimo::PRODUCT_CODE_RELAY !== $this->payload['letter']['service']['productCode']
            && isset($this->payload['letter']['parcel']['pickupLocationId'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The ProductCode used requires that a pickupLocationId is *not* set!')
            );
        }
    }

    protected function checkCommercialName()
    {
        if (Colissimo::PRODUCT_CODE_RELAY === $this->payload['letter']['service']['productCode']
            && (
                !isset($this->payload['letter']['service']['commercialName'])
                ||
                empty($this->payload['letter']['service']['commercialName'])
            )) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The ProductCode used requires that a commercialName is set!')
            );
        }
    }

    protected function checkSenderAddress()
    {
        $address = $this->payload['letter']['sender']['address'];

        if (empty($address['companyName'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('companyName must be set in Sender address!')
            );
        }

        if (empty($address['line2'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('line2 must be set in Sender address!')
            );
        }

        if (empty($address['countryCode'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('countryCode must be set in Sender address!')
            );
        }

        if (empty($address['zipCode'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('zipCode must be set in Sender address!')
            );
        }

        if (empty($address['city'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('city must be set in Sender address!')
            );
        }
    }

    protected function checkAddresseeAddress()
    {
        $address = $this->payload['letter']['addressee']['address'];

        if (empty($address['companyName'])
            && (empty($address['firstName']) || empty($address['lastName']))
        ) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('companyName or (firstName + lastName) must be set in Addressee address!')
            );
        }

        if ($this->isReturnLabel) {
            if (empty($address['companyName'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('companyName must be set in Addressee address for return label!')
                );
            }
        }

        if (empty($address['line2'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('line2 must be set in Addressee address!')
            );
        }

        if (empty($address['countryCode'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('countryCode must be set in Addressee address!')
            );
        }

        if (empty($address['zipCode'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('zipCode must be set in Addressee address!')
            );
        }

        if (empty($address['city'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('city must be set in Addressee address!')
            );
        }

        if (Colissimo::PRODUCT_CODE_RELAY === $this->payload['letter']['service']['productCode']
            && (
                !isset($address['mobileNumber'])
                ||
                empty($address['mobileNumber'])
            )) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The ProductCode used requires that a mobile number is set!')
            );
        }
    }

    protected function rebuildPiece(array $piece, $storeId = null)
    {
        $orderItem = $this->orderItemRepository->get($piece['order_item_id']);
        $order = $orderItem->getOrder();
        $product = $orderItem->getProduct();

        if (!isset($piece['weight']) || $piece['weight'] <= 0) {
            $piece['weight'] = $product->getWeight();
        }

        $piece['currency'] = $order->getOrderCurrencyCode();
        $piece['sku'] = $orderItem->getSku();
        $piece['country_of_manufacture'] = $product->getCountryOfManufacture();

        $hsCodeAttribute = $this->helperData->getAdvancedConfigValue('lpc_labels/hsCodeAttribute', $storeId);
        if (empty($hsCodeAttribute)) {
            $hsCodeAttribute = 'lpc_hs_code';
        }
        $piece['lpc_hs_code'] = $product->getData($hsCodeAttribute);

        return $piece;
    }

    /**
     * @param $trackingNumber
     */
    public function setOriginalTrackingNumber($trackingNumber)
    {
        $this->payload['letter']['customsDeclarations']['contents']['original'][0]['originalParcelNumber'] = $trackingNumber;
    }

    public function resetPayload()
    {
        $this->payload = [];

        return $this;
    }

    /**
     * Return payload without password for logging by example
     *
     * @return array
     */
    public function getPayloadWithoutPassword()
    {
        $payloadWithoutPass = $this->payload;

        unset($payloadWithoutPass['password']);

        return $payloadWithoutPass;
    }

    public function withPostalNetwork($countryCode, $productCode, $shippingMethod)
    {
        if (in_array($countryCode, ['AT', 'DE', 'IT', 'LU']) && Colissimo::PRODUCT_CODE_WITH_SIGNATURE === $productCode) {
            if (in_array($shippingMethod, [Colissimo::CODE_SHIPPING_METHOD_EXPERT, Colissimo::CODE_SHIPPING_METHOD_EXPERT_DDP])) {
                $network = $this->helperData->getConfigValue('carriers/lpc_group/expert_sendingservice_' . $countryCode);
            } else {
                $network = $this->helperData->getConfigValue('carriers/lpc_group/domicileas_sendingservice_' . $countryCode);
            }
            $this->payload['letter']['service']['reseauPostal'] = 'partner' === $network ? 1 : 0;
        }

        return $this;
    }

    public function withMultiShipping($order, $shipment, $multiShippingDataFromQuery, $shipmentDataFromQuery)
    {
        $multiShippingAmount = $order->getLpcMultiParcelsAmount();
        if (empty($multiShippingAmount)) {
            if (empty($multiShippingDataFromQuery['lpc_use_multi_parcels']) || $multiShippingDataFromQuery['lpc_use_multi_parcels'] !== 'on') {
                return $this;
            }
            if (empty($multiShippingDataFromQuery['lpc_multi_parcels_amount'])) {
                return $this;
            }

            $multiShippingAmount = $multiShippingDataFromQuery['lpc_multi_parcels_amount'];
        }

        if ($multiShippingAmount < 2 || $multiShippingAmount > 4) {
            $this->logger->error(
                'Incorrect number of parcels',
                ['given' => $multiShippingAmount, 'minimum' => 2, 'maximum' => 4]
            );
            throw new \Exception(__('Incorrect number of parcels'));
        }

        $currentShippingNumber = $shipment->getLpcMultiParcelsNumber();

        if (empty($currentShippingNumber)) {
            $currentShippingNumber = count($order->getShipmentsCollection());
        }

        // When we create a shipping label on the shipment creation the line above doesn't return the right amount
        if (!empty($shipmentDataFromQuery) && !empty($shipmentDataFromQuery['create_shipping_label']) && $shipmentDataFromQuery['create_shipping_label'] == 1) {
            $currentShippingNumber ++;
        }

        //This means that it's the first shipment
        if (empty($currentShippingNumber)) {
            $currentShippingNumber = 1;
        }

        if ($currentShippingNumber > $multiShippingAmount) {
            $this->logger->error(
                'All parcels already generated',
                ['given' => $multiShippingAmount, 'maximum' => $multiShippingAmount]
            );
            throw new \Exception(__('All labels have already been generated'));
        }


        $shippingType = $shipment->getLpcShippingType();

        if (empty($shippingType)) {
            $shippingType = $currentShippingNumber == $multiShippingAmount ? self::LABEL_TYPE_MASTER : self::LABEL_TYPE_FOLLOWER;
        }

        $this->payload['fields']['field'][] = [
            'key'   => 'TYPE_MULTI_PARCEL',
            'value' => $shippingType,
        ];

        if ($shippingType === self::LABEL_TYPE_MASTER) {
            $trackingNumbers = [];
            foreach ($order->getTracksCollection() as $oneTrack) {
                if (empty($oneTrack->getTrackNumber())) {
                    continue;
                }
                $trackingNumbers[] = $oneTrack->getTrackNumber();
            }
            $this->payload['fields']['field'][] = [
                'key'   => 'LIST_FOLLOWER_PARCEL',
                'value' => implode('/', $trackingNumbers),
            ];
        }

        $this->payload['fields']['field'][] = [
            'key'   => 'PARCEL_ITERATION_NUMBER',
            'value' => $currentShippingNumber,
        ];

        $this->payload['fields']['field'][] = [
            'key'   => 'TOTAL_NUMBER_PARCEL',
            'value' => $multiShippingAmount,
        ];

        return $this;
    }
}
