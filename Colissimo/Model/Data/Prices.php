<?php

namespace LaPoste\Colissimo\Model\Data;

use LaPoste\Colissimo\Api\Data\PricesInterface;

class Prices extends \Magento\Framework\Api\AbstractExtensibleObject implements PricesInterface
{

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId()
    {
        return $this->_get(self::ENTITY_ID);
    }

    /**
     * Set entity_id
     * @param string $entityId
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get method
     * @return string|null
     */
    public function getMethod()
    {
        return $this->_get(self::METHOD);
    }

    /**
     * Set method
     * @param string $method
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setMethod($method)
    {
        return $this->setData(self::METHOD, $method);
    }

    /**
     * Get method
     * @return string|null
     */
    public function getArea()
    {
        return $this->_get(self::AREA);
    }

    /**
     * Set method
     * @param string $area
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setArea($area)
    {
        return $this->setData(self::AREA, $area);
    }

    /**
     * Get price
     * @return string|null
     */
    public function getPrice()
    {
        return $this->_get(self::PRICE);
    }

    /**
     * Set price
     * @param string $price
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setPrice($price)
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * Get weight_min
     * @return string|null
     */
    public function getWeightMin()
    {
        return $this->_get(self::WEIGHT_MIN);
    }

    /**
     * Set weight_min
     * @param string $weightMin
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setWeightMin($weightMin)
    {
        return $this->setData(self::WEIGHT_MIN, $weightMin);
    }

    /**
     * Get weight_max
     * @return string|null
     */
    public function getWeightMax()
    {
        return $this->_get(self::WEIGHT_MAX);
    }

    /**
     * Set weight_max
     * @param string $weightMax
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setWeightMax($weightMax)
    {
        return $this->setData(self::WEIGHT_MAX, $weightMax);
    }

    /**
     * Get price_min
     * @return string|null
     */
    public function getPriceMin()
    {
        return $this->_get(self::PRICE_MIN);
    }

    /**
     * Set price_max
     * @param string $priceMin
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setPriceMin($priceMin)
    {
        return $this->setData(self::PRICE_MIN, $priceMin);
    }

    /**
     * Get price_max
     * @return string|null
     */
    public function getPriceMax()
    {
        return $this->_get(self::PRICE_MAX);
    }

    /**
     * Set price_max
     * @param string $priceMax
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface
     */
    public function setPriceMax($priceMax)
    {
        return $this->setData(self::PRICE_MAX, $priceMax);
    }


    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \LaPoste\Colissimo\Api\Data\PricesExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \LaPoste\Colissimo\Api\Data\PricesExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \LaPoste\Colissimo\Api\Data\PricesExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}

