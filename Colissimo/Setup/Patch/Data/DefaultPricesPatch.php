<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use LaPoste\Colissimo\Model\Prices\DefaultPrices;
use LaPoste\Colissimo\Model\PricesRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class DefaultPricesPatch implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private PricesRepository $pricesRepository;
    private DefaultPrices $defaultPrices;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PricesRepository $pricesRepository,
        DefaultPrices $defaultPrices
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->pricesRepository = $pricesRepository;
        $this->defaultPrices = $defaultPrices;
    }

    public static function getDependencies(): array
    {
        return [
            EavPatch::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->addDefaultPrices();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @throws LocalizedException
     */
    public function addDefaultPrices(): void
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $pricesItems = $this->pricesRepository->getList($searchCriteria)->getItems();

        // If there are prices already, skip the installation of default prices
        if (!empty($pricesItems)) {
            return;
        }

        $this->defaultPrices->insert();
    }
}
