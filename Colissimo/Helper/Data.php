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

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ADVANCED = 'lpc_advanced/';
    const MODULE_NAME = 'LaPoste_Colissimo';
    const LBS_IN_ONE_KG = 2.20462262185;

    protected $moduleList;
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;
    /**
     * @var SerializerInterface
     */
    protected $serializer;
    /**
     * @var CollectionFactory
     */
    protected $configCollection;
    private WriterInterface $configWriter;

    /**
     * Data constructor.
     *
     * @param Context                  $context
     * @param ModuleListInterface      $moduleList
     * @param ProductMetadataInterface $productMetadata
     * @param SerializerInterface      $serializer
     * @param CollectionFactory        $configCollection
     * @param WriterInterface          $configWriter
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        SerializerInterface $serializer,
        CollectionFactory $configCollection,
        WriterInterface $configWriter
    ) {
        $this->moduleList = $moduleList;
        $this->serializer = $serializer;
        $this->configCollection = $configCollection;
        $this->configWriter = $configWriter;
        parent::__construct($context);
        $this->productMetadata = $productMetadata;
    }

    public function getAdminRoute($controller, $action)
    {
        return 'laposte_colissimo/'
               . (!empty($controller) ? '' . "$controller/" : '')
               . (!empty($action) ? '' . "$action/" : '');
    }

    public function getConfigValue($field, $storeId = null, $bypassCache = false)
    {
        if ($bypassCache) {
            $collection = $this->configCollection->create();
            $collection->addFieldToFilter('path', ['eq' => $field]);
            if ($collection->count() > 0) {
                return $collection->getFirstItem()->getData()['value'];
            } else {
                return '';
            }
        } else {
            return $this->scopeConfig->getValue(
                $field,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
    }

    public function getAdvancedConfigValue($code, $storeId = null)
    {
        if (strpos($code, 'pwd_webservices') !== false) {
            $value = $this->getConfigValue(self::XML_PATH_ADVANCED . $code, $storeId, true);
            $value = base64_decode($value);
        } else {
            $value = $this->getConfigValue(self::XML_PATH_ADVANCED . $code, $storeId);
        }

        return $value;
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    public function isUsingColiShip($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ADVANCED . 'lpc_labels/isUsingColiShip',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return Colissimo module version
     * @return string
     */
    public function getModuleVersion()
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * Return Magento version
     * @return string
     */
    public function getMgVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Function to decode config value (json or serialize) depending on Magento version 2.2 or lower
     *
     * @param $valueEncoded
     *
     * @return mixed
     */
    public function decodeFromConfig($valueEncoded)
    {
        if (version_compare($this->getMgVersion(), '2.2.0', '>=')) {
            $decodedValue = json_decode($valueEncoded, true);
        } else {
            $decodedValue = $this->serializer->unserialize($valueEncoded);
        }

        return $decodedValue;
    }

    /**
     * Depending on Magento veriosn (2.2 or lower) data have different structures
     *
     * @param $dataJSonOrSerialize : object to get data from
     * @param $key                 : key to get value
     *
     * @return mixed
     */
    public function getValueDependingMgVersion($dataJSonOrSerialize, $key)
    {
        if (version_compare($this->getMgVersion(), '2.2.0', '>=')) {
            $value = $dataJSonOrSerialize->$key;
        } else {
            $value = $dataJSonOrSerialize[$key];
        }

        return $value;
    }

    /**
     * Return data to put in CuserInfoText in labelling
     *
     * @return string
     */
    public function getCuserInfoText(bool $isForColiship = false): string
    {
        $mageVersion = $this->getMgVersion();
        $colissimoVersion = $this->getModuleVersion();

        $cuserInfoTxt = 'MAG' . $mageVersion . ';' . $colissimoVersion;

        if ($isForColiship) {
            return $cuserInfoTxt . ';CLS';
        }

        return $cuserInfoTxt;
    }

    public function getMarkers(): array
    {
        $markers = $this->getConfigValue('lpc_advanced/lpc_general/markers', null, true);

        return empty($markers) ? [] : json_decode($markers, \JSON_OBJECT_AS_ARRAY);
    }

    public function setMarker(string $marker, $value): void
    {
        $markers = $this->getMarkers();
        $markers[$marker] = $value;
        $this->configWriter->save('lpc_advanced/lpc_general/markers', json_encode($markers));
    }

    public function convertWeightToKilogram($weight, $fromUnit, $storeId = null)
    {
        if (empty($fromUnit)) {
            if (empty($storeId)) {
                $shopUnit = $this->getConfigValue('general/locale/weight_unit');
            } else {
                $shopUnit = $this->getConfigValue('general/locale/weight_unit', $storeId);
            }

            if (strpos($shopUnit, 'lbs') !== false) {
                $fromUnit = 'LBS';
            } else {
                $fromUnit = 'KILOGRAM';
            }
        }

        $fromUnit = strtoupper($fromUnit);
        if (in_array($fromUnit, ['POUND', 'LBS'])) {
            $weight /= self::LBS_IN_ONE_KG;
        }

        return (double) $weight;
    }
}
