<?php

namespace LaPoste\Colissimo\Api\Data;

interface PricesSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Prices list.
     * @return \LaPoste\Colissimo\Api\Data\PricesInterface[]
     */
    public function getItems();

    /**
     * Set title list.
     * @param \LaPoste\Colissimo\Api\Data\PricesInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

