<?php

namespace LaPoste\Colissimo\Model\Prices;

use LaPoste\Colissimo\Api\Data\PricesInterface;
use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\PricesFactory;
use LaPoste\Colissimo\Model\ResourceModel\Prices\CollectionFactory;

class DefaultPrices
{
    const DEFAULT_PRICES_PER_ZONE_JSON_FILE = __DIR__ . '/../../resources/defaultPrices.json';
    const UNIT_LBS = 'LBS';

    protected Data $helperData;
    protected PricesFactory $pricesFactory;
    protected CollectionFactory $collectionFactory;

    public function __construct(
        Data $helperData,
        PricesFactory $pricesFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->helperData = $helperData;
        $this->pricesFactory = $pricesFactory;
        $this->collectionFactory = $collectionFactory;
    }

    public function reset(): int
    {
        foreach ($this->collectionFactory->create() as $onePrice) {
            $onePrice->delete();
        }

        return $this->insert();
    }

    public function insert(): int
    {
        $defaultPrices = json_decode(file_get_contents(self::DEFAULT_PRICES_PER_ZONE_JSON_FILE), \JSON_OBJECT_AS_ARRAY);

        if (empty($defaultPrices)) {
            return 0;
        }

        $weightUnit = $this->helperData->getConfigValue('general/locale/weight_unit');
        $conversion = self::UNIT_LBS === $weightUnit;
        $inserted = 0;

        foreach ($defaultPrices as $regionCode => $methodsPrices) {
            foreach ($methodsPrices as $methodCode => $prices) {
                foreach ($prices as $oneRow) {
                    $weightMin = $oneRow['weight_min'] / 1000;
                    // I kept the same format as in the WP version, that excludes the max weight, thus the "-1" here since the max weight is included
                    $weightMax = ($oneRow['weight_max'] - 1) / 1000;

                    if ($conversion) {
                        $weightMin = $this->convertGramToLbs($oneRow['weight_min']);
                        $weightMax = $this->convertGramToLbs($oneRow['weight_max'] - 1);
                    }

                    $this->pricesFactory->create()
                                        ->setData(
                                            [
                                                PricesInterface::AREA         => $regionCode,
                                                PricesInterface::METHOD       => $methodCode,
                                                PricesInterface::PRICE        => $oneRow['price'],
                                                PricesInterface::WEIGHT_MIN   => $weightMin,
                                                PricesInterface::WEIGHT_MAX   => $weightMax,
                                                PricesInterface::PRICE_MIN    => 0,
                                                PricesInterface::CATEGORY_IDS => '',
                                            ]
                                        )
                                        ->save();
                    $inserted ++;
                }
            }
        }

        return $inserted;
    }

    private function convertGramToLbs($weight): float
    {
        return (float) $weight * 0.00220462262185;
    }
}
