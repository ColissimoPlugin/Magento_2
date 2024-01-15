<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use LaPoste\Colissimo\Helper\Data;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class RenameWebserviceMap implements DataPatchInterface
{
    private $moduleDataSetup;
    private $configWriter;
    private Data $helperData;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter,
        Data $helperData
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
        $this->helperData = $helperData;
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
            $mapType = $this->helperData->getConfigValue('lpc_advanced/lpc_pr_front/choosePRDisplayMode');

            if ('webservice' === $mapType) {
                $this->moduleDataSetup->startSetup();
                $this->configWriter->save(
                    'lpc_advanced/lpc_pr_front/choosePRDisplayMode',
                    'gmaps'
                );
                $this->moduleDataSetup->endSetup();
            }
        } catch (\Exception $e) {
            // do nothing more
        }
    }
}

