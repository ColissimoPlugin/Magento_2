<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\Patch\PatchInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class HSCodePatch implements DataPatchInterface
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
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->addHSCodeAttribute();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Add product attribute to set specific HS code per product
     * @throws \Exception
     */
    protected function addHSCodeAttribute()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        try {
            $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            throw new \Exception($exception->getMessage() . ': ' . (Product::ENTITY));
        }
        if (!$eavSetup->getAttributeId($entityTypeId, 'lpc_hs_code')) {
            try {
                $eavSetup->addAttribute(
                    $entityTypeId,
                    'lpc_hs_code',
                    [
                        'type'                    => 'int',
                        'backend'                 => '',
                        'frontend'                => '',
                        'label'                   => 'Product HS Code',
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
