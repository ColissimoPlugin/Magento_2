<?php

namespace LaPoste\Colissimo\Api\Data;

interface PricesInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const ENTITY_ID = 'entity_id';
    const METHOD = 'method';
    const AREA = 'area';
    const PRICE = 'price';
    const WEIGHT_MIN = 'weight_min';
    const WEIGHT_MAX = 'weight_max';
    const PRICE_MIN = 'price_min';
    const PRICE_MAX = 'price_max';
    const CATEGORY_IDS = 'category_ids';

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     * @param string $entityId
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setEntityId($entityId);

    /**
     * Get method
     * @return string|null
     */
    public function getMethod();

    /**
     * Set method
     * @param string $method
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setMethod($method);

    /**
     * Get method
     * @return string|null
     */
    public function getArea();

    /**
     * Set method
     * @param string $area
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setArea($area);

    /**
     * Get price
     * @return string|null
     */
    public function getPrice();

    /**
     * Set price
     * @param string $price
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setPrice($price);

    /**
     * Get weight_min
     * @return string|null
     */
    public function getWeightMin();

    /**
     * Set weight_min
     * @param string $weightMin
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setWeightMin($weightMin);

    /**
     * Get weight_max
     * @return string|null
     */
    public function getWeightMax();

    /**
     * Set weight_max
     * @param string $weightMax
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setWeightMax($weightMax);

    /**
     * Get price_min
     * @return string|null
     */
    public function getPriceMin();

    /**
     * Set price_min
     * @param string $weightMin
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setPriceMin($weightMin);

    /**
     * Get price_max
     * @return string|null
     */
    public function getPriceMax();

    /**
     * Set price_max
     * @param string $weightMax
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setPriceMax($weightMax);

    /**
     * Get category_ids
     * @return string|null
     */
    public function getCategoryIds();

    /**
     * Set category_ids
     * @param string $categoryIds
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setCategoryIds($categoryIds);


    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \LaPoste\Colissimo\Api\Data\PricesExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \LaPoste\Colissimo\Api\Data\PricesExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \LaPoste\Colissimo\Api\Data\PricesExtensionInterface $extensionAttributes
    );
}

