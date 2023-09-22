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
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ADVANCED = 'lpc_advanced/';

    const MODULE_NAME = "LaPoste_Colissimo";

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
     * Data constructor.
     *
     * @param Context                  $context
     * @param ModuleListInterface      $moduleList
     * @param ProductMetadataInterface $productMetadata
     * @param SerializerInterface      $serializer
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        SerializerInterface $serializer
    ) {
        $this->moduleList = $moduleList;
        $this->serializer = $serializer;
        parent::__construct($context);
        $this->productMetadata = $productMetadata;
    }

    public function getAdminRoute($controller, $action)
    {
        return 'laposte_colissimo/'
               . (!empty($controller) ? '' . "$controller/" : '')
               . (!empty($action) ? '' . "$action/" : '');
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getAdvancedConfigValue($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_ADVANCED . $code, $storeId);
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
    public function getCuserInfoText()
    {
        $mageVersion = $this->getMgVersion();
        $colissimoVersion = $this->getModuleVersion();

        return "MAG" . $mageVersion . ";" . $colissimoVersion;
    }
}
