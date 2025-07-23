<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use LaPoste\Colissimo\Api\Data\PricesInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\Prices;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use LaPoste\Colissimo\Model\PricesRepository;

class DefaultPricesPatch implements DataPatchInterface
{
    const DEFAULT_PRICES_PER_ZONE_JSON_FILE = __DIR__ . '/../../../resources/defaultPrices.json';
    const UNIT_LBS = 'LBS';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var Data
     */
    private $helperData;
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var PricesRepository
     */
    private $pricesRepository;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Data                     $helperData
     * @param ObjectManagerInterface   $objectManager
     * @param SearchCriteriaBuilder    $searchCriteriaBuilder
     * @param PricesRepository         $pricesRepository
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Data $helperData,
        ObjectManagerInterface $objectManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PricesRepository $pricesRepository
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->helperData = $helperData;
        $this->objectManager = $objectManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->pricesRepository = $pricesRepository;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->addDefaultPrices();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addDefaultPrices()
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $pricesItems = $this->pricesRepository->getList($searchCriteria)->getItems();

        // If there are prices already, skip the installation of default prices
        if (!empty($pricesItems)) {
            return;
        }

        $weightUnit = $this->helperData->getConfigValue('general/locale/weight_unit');
        $defaultPrices = json_decode(file_get_contents(self::DEFAULT_PRICES_PER_ZONE_JSON_FILE), \JSON_OBJECT_AS_ARRAY);

        if (empty($defaultPrices)) {
            return;
        }

        $conversion = self::UNIT_LBS === $weightUnit;

        foreach ($defaultPrices as $regionCode => $methodsPrices) {
            foreach ($methodsPrices as $methodCode => $prices) {
                foreach ($prices as $oneRow) {
                    $newModelPrices = $this->objectManager->create(Prices::class)->load(null);

                    $weightMin = $oneRow['weight_min'] / 1000;
                    // I kept the same format as in the WP version, that excludes the max weight, thus the "-1" here since the max weight is included
                    $weightMax = ($oneRow['weight_max'] - 1) / 1000;

                    if ($conversion) {
                        $weightMin = $this->convertGramToLbs($oneRow['weight_min']);
                        $weightMax = $this->convertGramToLbs($oneRow['weight_max'] - 1);
                    }

                    $data = [
                        PricesInterface::AREA         => $regionCode,
                        PricesInterface::METHOD       => $methodCode,
                        PricesInterface::PRICE        => $oneRow['price'],
                        PricesInterface::WEIGHT_MIN   => $weightMin,
                        PricesInterface::WEIGHT_MAX   => $weightMax,
                        PricesInterface::PRICE_MIN    => 0,
                        PricesInterface::CATEGORY_IDS => '',
                    ];

                    $newModelPrices->setData($data);
                    $newModelPrices->save();
                }
            }
        }
    }

    private function convertGramToLbs($weight): float
    {
        return (float) $weight * 0.00220462262185;
    }
}
