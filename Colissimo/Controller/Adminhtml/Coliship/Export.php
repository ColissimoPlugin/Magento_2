<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Coliship;

use LaPoste\Colissimo\Helper\CountryOffer;
use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\AccountApi;
use LaPoste\Colissimo\Model\Carrier\Colissimo;
use LaPoste\Colissimo\Model\Carrier\GenerateLabelPayload;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\DataObject;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class Export extends Action
{
    const ADMIN_RESOURCE = 'LaPoste_Colissimo::shipment';
    const TEMP_FILE_PREFIX = 'lpc_colissimo_coliship_export.';
    const DOWNLOADED_FILENAME = 'coliship.export.csv';
    const ENTETE_COLIS = 'EXP';
    const ENTETE_ARTICLE = 'CN2';
    // Difference between the customs categories for label generation via API and via Coliship
    const CUSTOMS_CAT_CORRESPONDANCE = [
        0 => 5, // Other (default value)
        1 => 2, // Gift
        2 => 0, // Commercial sample
        3 => 4, // Commercial shipment
        4 => 1, // Document
        5 => 5, // Other
        6 => 3, // Return of articles
    ];
    const INSURANCE_STEPS = [
        150,
        300,
        500,
        1000,
        2000,
        5000,
    ];
    const SEPARATOR = ',';
    const ENCLOSURE = '"';
    const ESCAPE = '\\';

    protected $helperData;
    protected $countryHelperOffer;
    protected $orderRepository;
    protected $searchCriteriaBuilder;
    protected $fileFactory;
    protected $directoryList;
    protected $orderItemRepository;

    private AccountApi $accountApi;

    public function __construct(
        Context $context,
        Data $helperData,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FileFactory $fileFactory,
        DirectoryList $directoryList,
        CountryOffer $countryHelperOffer,
        OrderItemRepositoryInterface $orderItemRepository,
        AccountApi $accountApi
    ) {
        parent::__construct($context);
        $this->helperData = $helperData;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
        $this->countryHelperOffer = $countryHelperOffer;
        $this->orderItemRepository = $orderItemRepository;
        $this->accountApi = $accountApi;
    }

    public function execute()
    {
        if (!$this->helperData
            ->getAdvancedConfigValue('lpc_labels/isUsingColiShip')) {
            return $this->getResponse()
                        ->setStatusCode(Http::STATUS_CODE_412)
                        ->setContent('ColiShip is not activated!');
        }


        $orders = $this->getOrdersReadyToExport();
        $exportData = $this->convertForExport($orders);

        return $this->exportCsv($exportData);
    }

    private function getOrdersReadyToExport()
    {
        // retrieve all orders where
        $searchCriteriaBuilder = $this->searchCriteriaBuilder
            // shipment_method is colissimo
            ->addFilter(
                'shipping_method',
                Colissimo::CODE . '_%',
                'like'
            );

        $statusesForGeneration = $this->helperData->getAdvancedConfigValue('lpc_labels/orderStatusForGeneration');
        if (empty($statusesForGeneration)) {
            $orderStatusesForGeneration = [];
        } else {
            $orderStatusesForGeneration = explode(',', $statusesForGeneration);
        }

        if (!empty($orderStatusesForGeneration)) {
            // status == configuration status ready for generation

            $searchCriteriaBuilder = $searchCriteriaBuilder->addFilter('status', $orderStatusesForGeneration, 'in');
        }

        $searchCriteria = $searchCriteriaBuilder->create();

        $orders = $this->orderRepository
            ->getList($searchCriteria);


        $result = [];
        // only keep order not yet having tracking information
        foreach ($orders as $order) {
            if ($order->getTracksCollection()->getSize() > 0) {
                continue;
            }

            if (Order::STATE_CANCELED === $order->getStatus()) {
                continue;
            }

            $result[] = $order;
        }

        return $result;
    }

    private function convertForExport(array $orders): array
    {
        $accountInformation = $this->accountApi->getAccountInformation();
        $blockingCodeActive = !empty($accountInformation['statutCodeBloquant']);

        $dataRows = [];
        foreach ($orders as $order) {
            $row = [];
            $cn23Data = [];
            $storeId = $order->getStoreId();

            $shippingAddress = $order->getShippingAddress();
            $originCountryId = $this->helperData->getConfigValue('shipping/origin/country_id', $storeId);
            $insuranceConfig = $this->helperData->getConfigValue('lpc_advanced/lpc_labels/isUsingInsurance', $storeId);
            $shippingMethod = $order->getShippingMethod();
            $unprefixedShippingMethod = preg_replace('|^' . Colissimo::CODE . '_|', '', $shippingMethod);

            $request = new DataObject(
                [
                    'shipping_method'                => $unprefixedShippingMethod,
                    'recipient_address_country_code' => $shippingAddress->getCountryId(),
                    'recipient_address_postal_code'  => $shippingAddress->getPostcode(),
                ]
            );

            /* Marks the beginning of a new parcel */
            $row['entete_ligne_colis'] = self::ENTETE_COLIS;
            /* Order ID */
            $row['reference_expedition'] = $order->getIncrementId();
            $row['raison_sociale_expediteur'] = $this->helperData->getConfigValue('general/store_information/name', $storeId);
            $row['nom_commercial_chargeur'] = '';
            $row['prenom_expediteur'] = $this->helperData->getConfigValue('trans_email/ident_general/name', $storeId);
            /* Numéro et libellé de voie */
            $row['adresse_1_expediteur'] = $this->helperData->getConfigValue('general/store_information/street_line1', $storeId);
            /* Etage, couloir, escalier, appartement */
            $row['adresse_2_expediteur'] = $this->helperData->getConfigValue('general/store_information/street_line2', $storeId);
            /* Entrée, bâtiment, immeuble, Résidence */
            $row['adresse_3_expediteur'] = '';
            /* Lieu dit, complément */
            $row['adresse_4_expediteur'] = '';
            $row['code_postal_expediteur'] = $this->helperData->getConfigValue('general/store_information/postcode', $storeId);
            $row['commune_expediteur'] = $this->helperData->getConfigValue('general/store_information/city', $storeId);
            $row['code_pays_expediteur'] = $this->helperData->getConfigValue('general/store_information/country_id', $storeId);
            $row['mail_expediteur'] = $this->helperData->getConfigValue('trans_email/ident_general/email', $storeId);
            $row['telephone_expediteur'] = $this->helperData->getConfigValue('general/store_information/phone', $storeId);
            $row['raison_sociale'] = $shippingAddress->getCompany();
            $row['nom_destinataire'] = $shippingAddress->getLastname();
            $row['prenom'] = $shippingAddress->getFirstname();
            /* Numéro et libellé de voie */
            $row['adresse_1'] = $shippingAddress->getStreet()[0];
            /* Etage, couloir, escalier, appartement */
            $row['adresse_2'] = $shippingAddress->getStreet()[1] ?? '';
            /* Entrée, bâtiment, immeuble, résidence */
            $row['adresse_3'] = $shippingAddress->getStreet()[2] ?? '';
            /* Lieu dit, complément */
            $row['adresse_4'] = '';
            $row['code_postal'] = $shippingAddress->getPostcode();
            $row['commune'] = $shippingAddress->getCity();
            $row['code_pays'] = $shippingAddress->getCountryId();

            $row['code_iso_province'] = '';
            if (in_array($row['code_pays'], GenerateLabelPayload::COUNTRIES_NEEDING_STATE)) {
                $row['code_iso_province'] = $shippingAddress->getRegionCode();
            }

            $row['portable'] = $shippingAddress->getTelephone();
            $row['telephone'] = '';
            $row['mail'] = $shippingAddress->getEmail();
            $row['code_point_retrait'] = $order->getLpcRelayId();

            try {
                $row['code_produit'] = (Colissimo::CODE_SHIPPING_METHOD_RELAY === $unprefixedShippingMethod)
                    ? $order->getLpcRelayType()
                    : $this->countryHelperOffer->getProductCodeFromRequest($request, $originCountryId);
            } catch (\Exception $e) {
                continue;
            }

            $row['ftd'] = $this->countryHelperOffer->getFtdRequiredForDestination($shippingAddress->getCountryId(), $shippingAddress->getPostcode(), $originCountryId) === true
                          && $this->helperData->getAdvancedConfigValue('lpc_labels/isFtd', $storeId) ? 1 : 0;

            $row['langue_notification'] = 'fr';
            $row['livraison_avec_signature'] = in_array(
                $unprefixedShippingMethod,
                [
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS,
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS_DDP,
                ]
            ) ? 'O' : 'N';

            $totalWeight = 0;
            $totalPrice = 0;

            // Is CN23 needed for this order ?
            $cn23Needed = $this->countryHelperOffer->getIsCn23RequiredForDestination(
                $shippingAddress->getCountryId(),
                $shippingAddress->getPostcode(),
                $originCountryId,
                false
            );

            $defaultHsCode = $this->helperData->getAdvancedConfigValue('lpc_labels/defaultHsCode', $order->getStoreId());
            $hsCodeAttribute = $this->helperData->getAdvancedConfigValue('lpc_labels/hsCodeAttribute', $order->getStoreId());
            if (empty($hsCodeAttribute)) {
                $hsCodeAttribute = 'lpc_hs_code';
            }

            foreach ($order->getAllItems() as $item) {
                $itemQuantity = $item->getQtyOrdered();
                $itemWeight = $item->getWeight();
                $itemPrice = $item->getPrice();

                if (empty($itemQuantity)) {
                    $itemQuantity = $item->getQtyToShip();

                    if (empty($itemQuantity)) {
                        continue;
                    }
                }
                $itemQuantity = (int) $itemQuantity;

                if (empty($itemWeight) || empty($itemPrice)) {
                    $orderItem = $this->orderItemRepository->get($item->getId());
                    $product = $orderItem->getProduct();

                    if (empty($itemWeight)) {
                        $itemWeight = $product->getWeight();
                    }
                    if (empty($itemPrice)) {
                        $itemPrice = $product->getPrice();
                    }
                }

                $totalWeight += $itemWeight * $itemQuantity;
                $totalPrice += $itemPrice * $itemQuantity;

                // Prepare CN23 data
                if ($cn23Needed) {
                    $itemData = [];
                    // Tells Coliship it is a customs product line
                    $itemData['entet_ligne_article'] = self::ENTETE_ARTICLE;
                    // Should CN23 be added to the printed label
                    $itemData['cn23_imprimee'] = 1;
                    $itemData['libelle_article'] = $item->getName();
                    $itemData['poids_article'] = $itemWeight * 1000;
                    $itemData['quantite'] = $itemQuantity;
                    $itemData['valeur_article'] = (int) $itemPrice;
                    $itemData['pays_origine'] = $item->getProduct()->getCountryOfManufacture();
                    $itemData['devise'] = $order->getOrderCurrencyCode();
                    $itemData['reference_article'] = $item->getSku();
                    $itemData['num_tarifaire'] = $item->getProduct()->getData($hsCodeAttribute);
                    if (empty($itemData['num_tarifaire'])) {
                        $itemData['num_tarifaire'] = $defaultHsCode;
                    }
                    $cn23Data[] = $itemData;
                }
            }

            if ($row['livraison_avec_signature'] === 'O' && $row['code_pays'] === 'FR' && $blockingCodeActive) {
                $minimumOrderValue = $this->helperData->getAdvancedConfigValue('lpc_shipping/domicileas_block_code_min', $storeId);
                $maximumOrderValue = $this->helperData->getAdvancedConfigValue('lpc_shipping/domicileas_block_code_max', $storeId);

                if ((!empty($minimumOrderValue) && $totalPrice < $minimumOrderValue) || (!empty($maximumOrderValue) && $totalPrice > $maximumOrderValue)) {
                    $row['livraison_sans_code_bloquant'] = 'O';
                } else {
                    $row['livraison_sans_code_bloquant'] = 'N';
                }
            } else {
                $row['livraison_sans_code_bloquant'] = 'O';
            }

            $row['poids'] = $totalWeight * 1000;

            if ($cn23Needed) {
                $customsCategory = $this->helperData->getAdvancedConfigValue('lpc_labels/defaultCustomsCategory', $storeId);
                $row['nature_envoi'] = self::CUSTOMS_CAT_CORRESPONDANCE[$customsCategory] ?? 4;
            } else {
                $row['nature_envoi'] = '';
            }

            /* The insurance amount in centi-euros */
            $maxInsuranceAmount = $this->getMaxInsuranceAmountByProductCode($row['code_produit']);
            $insuranceAvailable = $this->countryHelperOffer->getInsuranceAvailableForDestination($row['code_pays'], $row['code_postal'], $originCountryId);

            if ($insuranceConfig && !empty($maxInsuranceAmount) && $insuranceAvailable) {
                $shipment = $order->getShipmentsCollection()->getFirstItem();

                if (!empty($shipment->getLpcInsuranceAmount())) {
                    $totalPrice = $shipment->getLpcInsuranceAmount();
                }

                $row['montant_adv'] = $this->getInsuranceAmount($totalPrice, $maxInsuranceAmount);
            } else {
                $row['montant_adv'] = '';
            }

            /* CuserInfoText for statistics */
            $row['tag_users'] = $this->helperData->getCuserInfoText(true);

            $dataRows[] = $row;
            foreach ($cn23Data as $cn23Row) {
                $dataRows[] = $cn23Row;
            }
        }

        return $dataRows;
    }

    private function getInsuranceAmount($totalPrice, $maxInsuranceAmount): int
    {
        $insuranceSteps = self::INSURANCE_STEPS;
        sort($insuranceSteps);
        $insuranceSteps = array_reverse($insuranceSteps);

        $insuranceValue = $maxInsuranceAmount;
        foreach ($insuranceSteps as $step) {
            if ($step > $maxInsuranceAmount) {
                continue;
            }

            if ($totalPrice > $step) {
                break;
            }

            $insuranceValue = $step;
        }

        return $insuranceValue;
    }

    private function getMaxInsuranceAmountByProductCode($productCode)
    {
        if (!in_array($productCode, Colissimo::PRODUCT_CODE_INSURANCE_AVAILABLE)) {
            return false;
        }

        return Colissimo::PRODUCT_CODE_RELAY === $productCode ? GenerateLabelPayload::MAX_INSURANCE_AMOUNT_RELAY : GenerateLabelPayload::MAX_INSURANCE_AMOUNT;
    }

    private function exportCsv(array $dataRows)
    {
        $tmpDir = $this->directoryList->getPath('tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $tmpFileName = \tempnam($tmpDir, self::TEMP_FILE_PREFIX);

        $tmpFile = fopen($tmpFileName, 'w');

        foreach ($dataRows as $row) {
            fputcsv($tmpFile, array_values($row), self::SEPARATOR, self::ENCLOSURE, self::ESCAPE);
        }

        fclose($tmpFile);

        // Specific case for Mg 2.1 which does not detect it is a full path to check if it's a file
        if (version_compare($this->helperData->getMgVersion(), '2.2.0', '<')) {
            $arrayPath = explode('/', $tmpFileName);
            $tmpFileName = '/tmp/' . end($arrayPath);
        }

        return $this->fileFactory->create(
            self::DOWNLOADED_FILENAME,
            [
                'type'  => 'filename',
                'value' => $tmpFileName,
                'rm'    => true,
            ],
            DirectoryList::VAR_DIR,
            'application/csv'
        );
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
