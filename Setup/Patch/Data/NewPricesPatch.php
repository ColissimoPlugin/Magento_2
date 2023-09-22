<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\PatchInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use LaPoste\Colissimo\Model\Carrier\Colissimo;
use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\Prices;
use Magento\Framework\ObjectManagerInterface;

class NewPricesPatch implements DataPatchInterface
{
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
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Data                     $helperData
     * @param ObjectManagerInterface   $objectManager
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Data $helperData,
        ObjectManagerInterface $objectManager
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->helperData = $helperData;
        $this->objectManager = $objectManager;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    /**
     * If the user updates from the 1.1.0 or lower, the patch will apply. If he updates from the 1.2.0 or later, the patch is skipped
     * @return string
     */
    public static function getVersion()
    {
        return '1.1.0';
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->migrateConfigPricesIntoPricesEntity();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function migrateConfigPricesIntoPricesEntity()
    {
        foreach (Colissimo::METHODS_CODES as $method) {
            if (!$this->helperData->getConfigValue('carriers/lpc_group/' . $method . '_enable')) {
                continue;
            }

            $maxPriceWeight = $this->helperData->getConfigValue('carriers/lpc_group/' . $method . '_maxweight');
            $basedOn = $this->helperData->getConfigValue('carriers/lpc_group/' . $method . '_priceorweight');
            $priceDataEncoded = $this->helperData->getConfigValue('carriers/lpc_group/' . $method . '_setup');
            $priceData = (array) $this->helperData->decodeFromConfig($priceDataEncoded);

            if (empty($priceData)) {
                continue;
            }

            $slicesKeys = array_keys($priceData);

            foreach ($slicesKeys as $key => $sliceKey) {
                $newModelPrices = $this->objectManager->create(Prices::class)->load(null);

                $slice = (object) $priceData[$sliceKey];
                $data = [
                    'area'   => $slice->area,
                    'method' => $method,
                    'price'  => $slice->price,
                ];
                if ($basedOn == 'cartweight') {
                    $data['weight_min'] = $slice->weight;
                } else {
                    $data['price_min'] = $slice->weight;
                }

                if (!empty($slicesKeys[$key + 1])) {
                    $nextSlice = (object) $priceData[$slicesKeys[$key + 1]];
                    if ($basedOn == 'cartweight') {
                        $data['weight_max'] = $nextSlice->weight;
                    } else {
                        $data['price_max'] = $nextSlice->weight;
                    }
                } elseif (!empty($maxPriceWeight)) {
                    if ($basedOn == 'cartweight') {
                        $data['weight_max'] = $maxPriceWeight;
                    } else {
                        $data['price_max'] = $maxPriceWeight;
                    }
                }

                $newModelPrices->setData($data);
                $newModelPrices->save();
            }
        }
    }
}
