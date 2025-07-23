<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use \LaPoste\Colissimo\Model\Carrier\Colissimo;
use LaPoste\Colissimo\Helper\Data;

class CountryOffer extends AbstractHelper
{
    const PATH_TO_COUNTRIES_PER_ZONE_JSON_FILE_FR = __DIR__ . '/../resources/capabilitiesByCountry.json';
    const PATH_TO_COUNTRIES_PER_ZONE_JSON_FILE_DOM1 = __DIR__ . '/../resources/capabilitiesByCountryDOM1.json';
    const CACHE_IDENTIFIER_PREFIX = 'lpc_country_offer_';
    const CACHE_IDENTIFIER_COUNTRIES_PER_ZONE_WITH_TRAD = self::CACHE_IDENTIFIER_PREFIX . 'countriesPerZoneWithTrad';
    const DOM1_COUNTRIES_CODE = ['BL', 'GF', 'GP', 'MQ', 'PM', 'RE', 'YT'];
    const DOM2_COUNTRIES_CODE = ['NC', 'PF', 'TF', 'WF'];
    const UNKNOWN_MG_COUNTRIES = ['BQ'];
    const COUNTRIES_FTD = ['GF', 'GP', 'MQ', 'RE'];

    //Some region have a specific country code that is not handle by Magento. The pattern is $countryCodeSpecificsDestinations['MagentoCountryCode']['startOfPostCode'] = "CustomLpcCountryCode"
    protected $countryCodeSpecificsDestinations = [
        'ES' => [
            '35' => 'IC',
            '38' => 'IC',
        ],
    ];

    private $productInfoByDestination;
    private $countriesPerZone;

    protected $cache;
    protected $helperData;
    protected $countryInformationAcquirer;

    public function __construct(
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformationAcquirer,
        \Magento\Framework\App\CacheInterface $cache,
        Data $helperData
    ) {
        $this->countryInformationAcquirer = $countryInformationAcquirer;
        $this->cache = $cache;
        $this->helperData = $helperData;
    }

    // Translate country and area names
    public function getCountriesPerZoneWithTrad()
    {
        $cachedInfo = $this->cache->load(self::CACHE_IDENTIFIER_COUNTRIES_PER_ZONE_WITH_TRAD);
        if (empty($cachedInfo)) {
            $countriesPerZone = $this->getCountriesPerZone();
            foreach ($countriesPerZone as &$oneZone) {
                $oneZone['name'] = __($oneZone['name']);
                foreach ($oneZone['countries'] as $countryCode => &$oneCountry) {
                    try {
                        $oneCountry['name'] = $this->countryInformationAcquirer
                            ->getCountryInfo($countryCode)
                            ->getFullNameLocale();
                    } catch (\Exception $e) {
                        $oneCountry["name"] = $countryCode == "IC" ? __("Canary Islands") : $countryCode;
                    }
                }
            }

            $this->cache->save(
                json_encode($countriesPerZone),
                self::CACHE_IDENTIFIER_COUNTRIES_PER_ZONE_WITH_TRAD
            );

            return $countriesPerZone;
        } else {
            return json_decode($cachedInfo, JSON_OBJECT_AS_ARRAY);
        }
    }

    /**
     * Get only slices of the correct destination (either country or area containing the country)
     * Warning : specific case for some spanish territories (country code still ES but some region code are not in the same Colissimo area and pricing)
     * @param       $methodCode              : shipping method code
     * @param       $destCountryId           : destination country code
     * @param       $priceItems              : configuration of the shipping method
     * @param       $destPostCode            : destination postcode
     * @param int   $cartPrice               : cart price
     * @param int   $cartWeight              : cart weight
     * @param       $originCountryId         : country code of origin
     * @param array $cartCategoriesByProduct : array of arrays => each product categories
     * @return array of slices ordered by price asc
     */
    public function getSlicesForDestination(
        $methodCode,
        $destCountryId,
        $priceItems,
        $destPostCode,
        $cartPrice,
        $cartWeight = 0,
        $originCountryId = 'fr',
        $cartCategoriesByProduct = []
    ) {
        $countriesPerZone = $this->getCountriesPerZone($originCountryId);
        $slicesDest = [];
        $maxPerArea = [];

        $lpcCountryCodeSpecificDestination = $this->getLpcCountryCodeSpecificDestination($destCountryId, $destPostCode);

        foreach ($priceItems as $oneItem) {
            $oneSliceArea = $oneItem->getArea();
            $oneSliceWeightMin = $oneItem->getWeightMin();
            $oneSliceWeightMax = $oneItem->getWeightMax();
            $oneSlicePriceMin = $oneItem->getPriceMin();
            $oneSlicePriceMax = $oneItem->getPriceMax();

            $sliceCategories = $oneItem->getCategoryIds();
            $oneSliceCategoryIds = trim(empty($sliceCategories) ? '' : $sliceCategories, ',');

            // We should filter prices based on product categories
            // If no categories in cart then don't filter
            if (!empty($oneSliceCategoryIds) && !empty($cartCategoriesByProduct)) {
                $sliceAllowedCategories = array_unique(array_filter(explode(',', $oneSliceCategoryIds)));

                foreach ($cartCategoriesByProduct as $oneCartProductCategories) {
                    if (empty(array_intersect($sliceAllowedCategories, $oneCartProductCategories))) {
                        // The product doesn't have at least one whitelisted category, ignore this slice
                        continue 2;
                    }
                }
            }

            if ($methodCode != $oneItem->getMethod()) {
                continue;
            }

            if ($cartWeight < $oneSliceWeightMin || (0 != $oneSliceWeightMax && $cartWeight > $oneSliceWeightMax)) {
                continue;
            }
            if ($cartPrice < $oneSlicePriceMin || (0 != $oneSlicePriceMax && $cartPrice > $oneSlicePriceMax)) {
                continue;
            }

            if ($oneSliceArea == $destCountryId) {
                //Slice is a country (not a specific country or not a specific region)
                if ($lpcCountryCodeSpecificDestination === false) {
                    $methodActive = false;
                    foreach ($countriesPerZone as $zone) {
                        if (!empty($zone['countries'][$destCountryId][$methodCode])) {
                            $methodActive = true;
                            break;
                        }
                    }

                    if ($methodActive) {
                        $slicesDest[] = $oneItem;
                        $this->checkNearestSlice($oneItem, $maxPerArea);
                    }
                }
            } elseif ($lpcCountryCodeSpecificDestination !== false && $lpcCountryCodeSpecificDestination == $oneSliceArea) {
                // Slice is a specific code. We should add if the destination is one of the specific
                $slicesDest[] = $oneItem;
                $this->checkNearestSlice($oneItem, $maxPerArea);
            } elseif (array_key_exists($oneSliceArea, $countriesPerZone)
                      && array_key_exists($destCountryId, $countriesPerZone[$oneSliceArea]['countries'])
                      && $countriesPerZone[$oneSliceArea]['countries'][$destCountryId][$methodCode]
                      && $lpcCountryCodeSpecificDestination === false) {
                // Area (Z1 to Z6) not a specific case
                $slicesDest[] = $oneItem;
                $this->checkNearestSlice($oneItem, $maxPerArea);
            } elseif ($lpcCountryCodeSpecificDestination !== false) {
                // Area (Z1 to Z6) specific case
                if (array_key_exists($oneSliceArea, $countriesPerZone)
                    && array_key_exists($lpcCountryCodeSpecificDestination, $countriesPerZone[$oneSliceArea]['countries'])
                    && $countriesPerZone[$oneSliceArea]['countries'][$lpcCountryCodeSpecificDestination][$methodCode]) {
                    $slicesDest[] = $oneItem;
                    $this->checkNearestSlice($oneItem, $maxPerArea);
                }
            }
        }

        // Remove slice if exist a more weight corresponding slice for the same area
        foreach ($slicesDest as $key => $oneSlice) {
            $oneSliceArea = $oneSlice->getArea();
            $oneSliceWeightMin = $oneSlice->getWeightMin();
            if ($oneSliceWeightMin != $maxPerArea[$oneSliceArea]) {
                unset($slicesDest[$key]);
            }
        }

        // Sort by price asc to get only the cheaper
        usort($slicesDest, [$this, "sortPerPrice"]);

        return $slicesDest;
    }

    /**
     * Add to
     * @param $slice
     * @param $maxPerArea
     */
    protected function checkNearestSlice($slice, &$maxPerArea)
    {
        $sliceWeightMin = $slice->getWeightMin();
        $sliceArea = $slice->getArea();
        if (!array_key_exists($sliceArea, $maxPerArea) || $sliceWeightMin > $maxPerArea[$sliceArea]) {
            $maxPerArea[$sliceArea] = $sliceWeightMin;
        }
    }

    /**
     * Order the slices by price asc
     * @param $a
     * @param $b
     * @return int
     */
    protected function sortPerPrice($a, $b)
    {
        $priceA = $a->getPrice();
        $priceB = $b->getPrice();
        if ($priceA == $priceB) {
            return 0;
        }

        return ($priceA < $priceB) ? - 1 : 1;
    }

    /**
     * Return product code
     * @param      $methodCode
     * @param      $destinationCountryId
     * @param      $destinationPostalCode
     * @param bool $isReturn
     * @return bool|string
     */
    public function getProductCodeForDestination(
        $methodCode,
        $destinationCountryId,
        $destinationPostalCode,
        $originCountryId,
        $isReturn = false
    ) {
        $productInfo = $this->getProductInfoForDestination($destinationCountryId, $destinationPostalCode, $originCountryId);

        if ($isReturn) {
            if (true === $productInfo['return']) {
                if (in_array($destinationCountryId, self::DOM1_COUNTRIES_CODE) && $this->isIntraDOM1($originCountryId, $destinationCountryId)) {
                    $productInfo['return'] = Colissimo::PRODUCT_CODE_RETURN_FRANCE;
                } else {
                    $productInfo['return'] = Colissimo::PRODUCT_CODE_RETURN_INT;
                }
            }

            return !empty($productInfo['return']) ? $productInfo['return'] : false;
        }

        switch ($methodCode) {
            case Colissimo::CODE_SHIPPING_METHOD_DOMICILE_SS:
                if (empty($productInfo[$methodCode])) {
                    return false;
                }
                if (in_array($destinationCountryId, self::DOM1_COUNTRIES_CODE)) {
                    if ($this->isIntraDOM1($originCountryId, $destinationCountryId)) {
                        return Colissimo::PRODUCT_CODE_WITHOUT_SIGNATURE_INTRA_DOM;
                    } else {
                        return Colissimo::PRODUCT_CODE_WITHOUT_SIGNATURE_OM;
                    }
                }

                return $productInfo[$methodCode];
            case Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS:
            case Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS_DDP:
                if (empty($productInfo[$methodCode])) {
                    return false;
                }
                if (in_array($destinationCountryId, self::DOM1_COUNTRIES_CODE)) {
                    if ($this->isIntraDOM1($originCountryId, $destinationCountryId)) {
                        return Colissimo::PRODUCT_CODE_WITH_SIGNATURE_INTRA_DOM;
                    } else {
                        return Colissimo::PRODUCT_CODE_WITH_SIGNATURE_OM;
                    }
                }

                return $productInfo[$methodCode];
            case Colissimo::CODE_SHIPPING_METHOD_EXPERT:
            case Colissimo::CODE_SHIPPING_METHOD_EXPERT_DDP:
                return $productInfo[$methodCode] ? Colissimo::PRODUCT_CODE_WITH_SIGNATURE : false;
            case Colissimo::CODE_SHIPPING_METHOD_RELAY:
            default:
                throw new \Exception('Shipping method not managed');
        }
    }

    protected function getProductInfoForDestination($destinationCountryId, $destinationPostcode, $originCountryId = 'fr')
    {
        $countryCodeSpecificDestination = $this->getLpcCountryCodeSpecificDestination($destinationCountryId, $destinationPostcode);

        $destinationCountryCode = $countryCodeSpecificDestination === false ? $destinationCountryId : $countryCodeSpecificDestination;

        if (null === $this->productInfoByDestination) {
            $this->productInfoByDestination = [];
            foreach ($this->getCountriesPerZone($originCountryId) as $zone) {
                foreach ($zone['countries'] as $countryCode => $productInfo) {
                    $this->productInfoByDestination[$countryCode] = $productInfo;
                }
            }
        }

        return $this->productInfoByDestination[$destinationCountryCode];
    }

    public function getIsCn23RequiredForDestination($destinationCountryId, $destinationPostcode, $originCountryId, $isReturnLabel)
    {
        // From DOM1 destinations, we don't need CN23 if we sent from and to the same island
        if (in_array($destinationCountryId, self::DOM1_COUNTRIES_CODE) && $originCountryId == $destinationCountryId) {
            return false;
        }

        if ('MC' === $destinationCountryId && !$isReturnLabel) {
            return false;
        }

        $productInfo = $this->getProductInfoForDestination($destinationCountryId, $destinationPostcode, $originCountryId);

        return $productInfo['cn23'];
    }

    public function getInsuranceAvailableForDestination($destinationCountryId, $destinationPostcode, $originCountryId = 'fr')
    {
        $productInfo = $this->getProductInfoForDestination($destinationCountryId, $destinationPostcode, $originCountryId);

        return $productInfo['insurance'];
    }

    public function getProductCodeFromRequest(\Magento\Framework\DataObject $request, $originCountryId, $isReturn = false)
    {
        if (Colissimo::CODE_SHIPPING_METHOD_RELAY === $request->getShippingMethod()) {
            if ($isReturn) {
                return $this->getProductCodeForDestination(
                    $request->getShippingMethod(),
                    $request['shipper_address_country_code'],
                    $request->getShipperAddressPostalCode(),
                    $originCountryId,
                    true
                );
            }

            return Colissimo::PRODUCT_CODE_RELAY;
        }

        $countryCode = $isReturn ? $request['shipper_address_country_code'] : $request['recipient_address_country_code'];
        $postcode = $isReturn ? $request['shipper_address_postal_code'] : $request['recipient_address_postal_code'];

        return $this->getProductCodeForDestination(
            $request->getShippingMethod(),
            $countryCode,
            $postcode,
            $originCountryId,
            $isReturn
        );
    }

    public function getCountriesPerZone($from = null)
    {
        if (null === $from) {
            $from = $this->helperData->getConfigValue('shipping/origin/country_id');
        }
        if (!empty($from) && in_array(strtoupper($from), self::DOM1_COUNTRIES_CODE)) {
            $configurationFile = self::PATH_TO_COUNTRIES_PER_ZONE_JSON_FILE_DOM1;
        } else {
            $configurationFile = self::PATH_TO_COUNTRIES_PER_ZONE_JSON_FILE_FR;
        }

        $this->countriesPerZone = json_decode(file_get_contents($configurationFile), \JSON_OBJECT_AS_ARRAY);
        foreach ($this->countriesPerZone as $zoneId => $zone) {
            foreach ($zone['countries'] as $countryId => $capabilities) {
                if (in_array($countryId, self::UNKNOWN_MG_COUNTRIES)) {
                    unset($zone['countries'][$countryId]);
                }
            }
            $this->countriesPerZone[$zoneId] = $zone;
        }

        return $this->countriesPerZone;
    }


    /**
     * Get the Colissimo specific destination country code from a magento country code and a postcode. Used for capabilitiesByCountry.json
     * @param $countryCode
     * @param $postCode
     * @return bool|string
     */
    public function getLpcCountryCodeSpecificDestination($countryCode, $postCode)
    {
        if (array_key_exists($countryCode, $this->countryCodeSpecificsDestinations)) {
            foreach ($this->countryCodeSpecificsDestinations[$countryCode] as $oneStartPostCode => $oneLpcRegionCode) {
                if (strpos($postCode, strval($oneStartPostCode)) === 0) {
                    return $oneLpcRegionCode;
                }
            }
        }

        return false;
    }

    /**
     * Retrieve Magento Country Code from a country code specific destination
     * @param $countryCodeSpecificDestination
     * @return bool|string
     */
    public function getMagentoCountryCodeFromSpecificDestination($countryCodeSpecificDestination)
    {
        foreach ($this->countryCodeSpecificsDestinations as $oneCountryCode => $oneCountryCodeSpecificsDestinations) {
            if (in_array($countryCodeSpecificDestination, $oneCountryCodeSpecificsDestinations)) {
                return $oneCountryCode;
            }
        }

        return false;
    }

    /**
     * Get all countries available for a delivery method
     * @param        $method
     * @param string $originCountryId
     * @return array
     */
    public function getCountriesForMethod($method, $originCountryId = 'fr')
    {
        $countriesOfMethod = [];
        $countriesPerZone = $this->getCountriesPerZone($originCountryId);

        foreach ($countriesPerZone as &$oneZone) {
            foreach ($oneZone['countries'] as $countryCode => &$oneCountry) {
                if ($oneCountry[$method] !== false) {
                    $countriesOfMethod[] = $countryCode;
                }
            }
        }

        return $countriesOfMethod;
    }

    /**
     * List all countries from one zone
     * @param        $zone
     * @param string $orignCountryId
     * @return array
     */
    public function getCountriesFromOneZone($zone, $orignCountryId = 'fr')
    {
        $countriesOfZone = [];
        $countriesPerZone = $this->getCountriesPerZone($orignCountryId);

        if (!empty($countriesPerZone[$zone]['countries'])) {
            foreach ($countriesPerZone[$zone]['countries'] as $countryCode => &$oneZone) {
                $countriesOfZone[] = $countryCode;
            }
        } else {
            $countriesOfZone[] = $zone;
        }

        return $countriesOfZone;
    }

    /**
     * @param $storeCountryCode string starting country code from
     * @param $countryCode      string destination counttry code
     *
     * @return bool
     */
    protected function isIntraDOM1($storeCountryCode, $countryCode)
    {
        // For expedition between these destinations, Colissimo considers it as intra
        $intraCountryCodes = ['GP', 'MQ', 'MF', 'BL'];

        if ($storeCountryCode == $countryCode) {
            return true;
        }

        if (in_array($storeCountryCode, $intraCountryCodes) && in_array($countryCode, $intraCountryCodes)) {
            return true;
        }

        return false;
    }

}
