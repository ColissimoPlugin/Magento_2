<?php

namespace LaPoste\Colissimo\Api;

use LaPoste\Colissimo\Api\Data\PricesInterface;
use LaPoste\Colissimo\Api\Data\PricesSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface PricesRepositoryInterface
{

    /**
     * Save Prices
     * @param PricesInterface $prices
     * @return PricesInterface
     * @throws LocalizedException
     */
    public function save(
        PricesInterface $prices
    );

    /**
     * Retrieve Prices
     * @param string $entityId
     * @return PricesInterface
     * @throws LocalizedException
     */
    public function get($entityId);

    /**
     * Retrieve Prices matching the specified criteria.
     * @param SearchCriteriaInterface $searchCriteria
     * @param string                  $attribute
     * @param string                  $value
     * @return PricesSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria,
        $attribute = null,
        $value = null
    );

    /**
     * Delete Prices
     * @param PricesInterface $prices
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(
        PricesInterface $prices
    );

    /**
     * Delete Prices by ID
     * @param string $entityId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($entityId);
}

