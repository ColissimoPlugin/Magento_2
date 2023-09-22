<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class CleanParentIdPatch implements DataPatchInterface
{
    private $moduleDataSetup;
    private $configWriter;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
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
        try {
            $this->moduleDataSetup->startSetup();
            $this->configWriter->save(
                'lpc_advanced/lpc_general/parent_id_webservices',
                '',
                \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
            $this->moduleDataSetup->endSetup();
        } catch (\Exception $e) {
            // do nothing more
        }
    }
}

