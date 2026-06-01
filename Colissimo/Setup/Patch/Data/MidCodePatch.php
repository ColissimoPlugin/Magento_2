<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\Patch\PatchInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class MidCodePatch implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->addMidCodeAttribute();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Add product attribute to set MID code to be used in CN23
     *
     * @throws \Exception
     */
    protected function addMidCodeAttribute(): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        try {
            $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        } catch (LocalizedException $exception) {
            throw new \Exception($exception->getMessage() . ': ' . (Product::ENTITY));
        }

        if (!$eavSetup->getAttributeId($entityTypeId, 'lpc_mid_code')) {
            try {
                $eavSetup->addAttribute(
                    $entityTypeId,
                    'lpc_mid_code',
                    [
                        'type'                    => 'varchar',
                        'length'                  => 50,
                        'backend'                 => '',
                        'frontend'                => '',
                        'label'                   => 'MID Code',
                        'input'                   => 'text',
                        'class'                   => '',
                        'source'                  => '',
                        'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible'                 => true,
                        'required'                => false,
                        'user_defined'            => false,
                        'default'                 => '',
                        'searchable'              => false,
                        'filterable'              => false,
                        'comparable'              => false,
                        'visible_on_front'        => false,
                        'used_in_product_listing' => false,
                        'unique'                  => false,
                        'apply_to'                => '',
                    ]
                );
            } catch (LocalizedException|\Exception $e) {
            }
        }
    }
}
