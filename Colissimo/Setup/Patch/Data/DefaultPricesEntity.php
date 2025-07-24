<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use LaPoste\Colissimo\Setup\PricesSetup;
use LaPoste\Colissimo\Setup\PricesSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class DefaultPricesEntity implements DataPatchInterface
{

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var PricesSetup
     */
    private $pricesSetupFactory;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param PricesSetupFactory       $pricesSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        PricesSetupFactory $pricesSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->pricesSetupFactory = $pricesSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var PricesSetup $customerSetup */
        $pricesSetup = $this->pricesSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $pricesSetup->installEntities();


        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }
}

