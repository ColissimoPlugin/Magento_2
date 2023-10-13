<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Controller\Adminhtml\Coliship;

use \Magento\Framework\App\Filesystem\DirectoryList;
use \LaPoste\Colissimo\Model\Carrier\Colissimo;

class Export extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'LaPoste_Colissimo::shipment';
    const TEMP_FILE_PREFIX = 'lpc_colissimo_coliship_export.';
    const DOWNLOADED_FILENAME = 'coliship.export.csv';
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

    protected $helperData;
    protected $countryHelperOffer;

    protected $orderRepository;
    protected $searchCriteriaBuilder;
    protected $fileFactory;
    protected $directoryList;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \LaPoste\Colissimo\Helper\Data $helperData,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        DirectoryList $directoryList,
        \LaPoste\Colissimo\Helper\CountryOffer $countryHelperOffer
    ) {
        parent::__construct($context);
        $this->helperData = $helperData;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
        $this->countryHelperOffer = $countryHelperOffer;
    }

    public function execute()
    {
        if (!$this->helperData
            ->getAdvancedConfigValue('lpc_labels/isUsingColiShip')) {
            return $this->getResponse()
                        ->setStatusCode(\Magento\Framework\App\Response\Http::STATUS_CODE_412)
                        ->setContent('ColiShip is not activated!');
        }


        $orders = $this->getOrdersReadyToExport();
        $exportData = $this->convertForExport($orders);
        $this->exportCsv($exportData);
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

            $result[] = $order;
        }

        return $result;
    }

    private function convertForExport(array $orders)
    {
        $dataRows = [];

        foreach ($orders as $order) {
            $row = [];
            $cn23Data = [];

            $shippingAddress = $order->getShippingAddress();

            $originCountryId = $this->helperData->getConfigValue(
                'shipping/origin/country_id',
                $order->getStoreId()
            );

            /* Code Produit */
            $shippingMethod = $order->getShippingMethod();
            $unprefixedShippingMethod = preg_replace(
                '|^' . Colissimo::CODE . '_|',
                '',
                $shippingMethod
            );
            $request = new \Magento\Framework\DataObject(
                [
                    'shipping_method'                => $unprefixedShippingMethod,
                    'recipient_address_country_code' => $shippingAddress->getCountryId(),
                    'recipient_address_postal_code'  => $shippingAddress->getPostcode(),
                ]
            );

            $row['product_code'] = (Colissimo::CODE_SHIPPING_METHOD_RELAY === $unprefixedShippingMethod)
                ? $order->getLpcRelayType()
                : $this->countryHelperOffer->getProductCodeFromRequest($request, $originCountryId, false);

            /* Nom du destinataire */
            $row['recipient_lastname'] = $shippingAddress->getLastname();
            /* Prénom du destinataire */
            $row['recipient_firstname'] = $shippingAddress->getFirstname();
            /* Adresse 1 du destinataire : Numéro et libellé de voie */
            $row['recipient_street1'] = $shippingAddress->getStreet()[0];
            /* Adresse 2 du destinataire : Etage, couloir, escalier, appartement */
            $row['recipient_street2'] = $shippingAddress->getStreet()[1] ?? '';
            /* Adresse 3 de destinataire : Entrée, bâtiment, immeuble, résidence */
            $row['recipient_street3'] = $shippingAddress->getStreet()[2] ?? '';
            /* Code postal du destinataire */
            $row['recipient_postCode'] = $shippingAddress->getPostcode();
            /* Commune du destinataire */
            $row['recipient_city'] = $shippingAddress->getCity();
            /* Code pays du destinataire */
            $row['recipient_country'] = $shippingAddress->getCountryId();
            /* Portable du destinataire */
            $row['recipient_phone'] = $shippingAddress->getTelephone();
            /* Adresse e-mail du destinataire */
            $row['recipient_email'] = $shippingAddress->getEmail();
            /* Poids */
            $totalWeight = 0;

            // Is CN23 needed for this order ?
            $cn23Needed = $this->countryHelperOffer->getIsCn23RequiredForDestination(
                $shippingAddress->getCountryId(),
                $shippingAddress->getPostcode(),
                $originCountryId,
                false);

            foreach ($order->getAllItems() as $item) {
                $totalWeight += $item->getWeight() * $item->getQtyToShip();
                // Prepare CN23 data
                if ($cn23Needed) {
                    $itemData = [];
                    $itemData['enteLigneArticle'] = self::ENTETE_ARTICLE; // Tells Coliship it is a product line
                    $itemData['cn23Imprime'] = 1; // Should CN23 be added to the printed label
                    $itemData['LibelleArticle'] = $item->getName();
                    $itemData['poidsArticle'] = $item->getWeight();
                    $itemData['quantite'] = $item->getQtyToShip();
                    $itemData['paysOrigine'] = $item->getProduct()->getCountryOfManufacture();
                    $itemData['valeurArticle'] = $item->getPrice();
                    $itemData['devise'] = $order->getOrderCurrencyCode();
                    $itemData['referenceArticle'] = $item->getSku();
                    $itemData['numTarifaire'] = $item->getProduct()->getLpcHsCode();
                    $cn23Data[] = $itemData;
                }
            }
            $row['weight'] = $totalWeight;
            /* Référence de commande */
            $row['orderId'] = $order->getIncrementId();
            /* Code point retrait */
            $row['pickupLocationId'] = $order->getLpcRelayId();
            /* Nom commercial de l'expéditeur */
            $row['sender_companyName'] = $this->helperData->getConfigValue(
                'general/store_information/name',
                $order->getStoreId()
            );
            /* Nom expéditeur */
            $row['sender_firstname'] = '';
            /* Prénom expéditeur */
            $row['sender_lastname'] = '';
            /* Adresse 1 de l'expéditeur : Numéro et libellé de voie */
            $row['sender_street'] = $this->helperData->getConfigValue(
                'general/store_information/street_line1',
                $order->getStoreId()
            );
            /* Adresse 2 de l'expéditeur : Etage, couloir, escalier, appartement */
            $row['sender_street2'] = $this->helperData->getConfigValue(
                'general/store_information/street_line2',
                $order->getStoreId()
            );
            /* Adresse 3 de l'expéditeur : Entrée, bâtiment, immeuble, Résidence */
            $row['sender_street3'] = $this->helperData->getConfigValue(
                'general/store_information/street_line3',
                $order->getStoreId()
            );
            /* Code pays de l'expéditeur */
            $row['sender_countryCode'] = $this->helperData->getConfigValue(
                'general/store_information/country_id',
                $order->getStoreId()
            );
            /* Code postal de l'expéditeur */
            $row['sender_zipCode'] = $this->helperData->getConfigValue(
                'general/store_information/postcode',
                $order->getStoreId()
            );
            /* Commune de l'expéditeur */
            $row['sender_city'] = $this->helperData->getConfigValue(
                'general/store_information/city',
                $order->getStoreId()
            );

            /* CuserInfoText pour les statistiques*/
            $row['tag_users'] = $this->helperData->getCuserInfoText();

            $useFtd = $this->countryHelperOffer->getFtdRequiredForDestination($shippingAddress->getCountryId(), $shippingAddress->getPostcode(), $originCountryId) === true
                      && $this->helperData->getAdvancedConfigValue('lpc_labels/isFtd', $order->getStoreId()) ? 1 : 0;
            $row['ftd'] = $useFtd;

            if ($cn23Needed) {
                $row['nature_envoi'] = self::CUSTOMS_CAT_CORRESPONDANCE[$this->helperData->getAdvancedConfigValue('lpc_labels/defaultCustomsCategory', $order->getStoreId())];
            } else {
                $row['nature_envoi'] = '';
            }

            $dataRows[] = $row;
            foreach ($cn23Data as $cn23Row) {
                $dataRows[] = $cn23Row;
            }
        }

        return $dataRows;
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
            fputcsv($tmpFile, array_values($row));
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
