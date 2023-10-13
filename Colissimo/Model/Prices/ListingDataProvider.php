<?php

namespace LaPoste\Colissimo\Model\Prices;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

/**
 * Class DataProvider for the listing
 */
class ListingDataProvider extends DataProvider
{

    public function getData()
    {
        $listingData = parent::getData();
        foreach ($listingData['items'] as &$onePrice) {
            if (0 == $onePrice['price_max']) {
                $onePrice['price_max'] = '-';
            }
            if (0 == $onePrice['weight_max']) {
                $onePrice['weight_max'] = '-';
            }
        }

        return $listingData;
    }
}
