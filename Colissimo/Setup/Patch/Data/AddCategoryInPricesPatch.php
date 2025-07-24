<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

use LaPoste\Colissimo\Model\Prices;

class AddCategoryInPricesPatch implements DataPatchInterface
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
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory          $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public static function getDependencies()
    {
        return [
            EavPatch::class,
        ];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->addCategoryInPrices();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Add product attribute to set specific HS code per product
     * @throws \Exception
     */
    protected function addCategoryInPrices()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        try {
            $entityTypeId = $eavSetup->getEntityTypeId(Prices::ENTITY);
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            throw new \Exception($exception->getMessage() . ': ' . (Prices::ENTITY));
        }
        if (!$eavSetup->getAttributeId($entityTypeId, 'category_ids')) {
            try {
                $eavSetup->addAttribute(
                    $entityTypeId,
                    'category_ids',
                    [
                        'type'     => 'static',
                        'backend'  => '',
                        'frontend' => '',
                        'label'    => 'Category IDs',
                        'input'    => 'text',
                        'global'   => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible'  => true,
                        'required' => false,
                        'default'  => '',
                        'unique'   => false,
                    ]
                );
            } catch (LocalizedException|\Exception $e) {
            }
        }
    }
}
