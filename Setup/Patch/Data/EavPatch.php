<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\PatchInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use LaPoste\Colissimo\Setup\PricesSetupFactory;
use LaPoste\Colissimo\Model\Prices;

class EavPatch implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
    /**
     * @var PricesSetupFactory
     */
    private $pricesSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory          $eavSetupFactory
     * @param PricesSetupFactory       $pricesSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        PricesSetupFactory $pricesSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->pricesSetupFactory = $pricesSetupFactory;
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

        $this->addPriceEntity();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    protected function addPriceEntity()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        if (!$eavSetup->getEntityType(Prices::ENTITY)) {
            $pricesSetup = $this->pricesSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $pricesSetup->installEntities();
        }
    }
}
