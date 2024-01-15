<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use LaPoste\Colissimo\Helper\Data;
use Magento\Framework\App\Config\Storage\WriterInterface;

class PasswordEncodingPatch implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private Data $helperData;
    private WriterInterface $configWriter;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Data                     $helperData
     * @param WriterInterface          $configWriter
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Data $helperData,
        WriterInterface $configWriter
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->helperData = $helperData;
        $this->configWriter = $configWriter;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->encodeSavedPassword();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function encodeSavedPassword()
    {
        $currentPassword = $this->helperData->getConfigValue('lpc_advanced/lpc_general/pwd_webservices');
        if (!empty($currentPassword)) {
            $encodedPassword = base64_encode($currentPassword);
            $this->configWriter->save('lpc_advanced/lpc_general/pwd_webservices', $encodedPassword);
            $this->helperData->setMarker('lpc_encoded_password', $encodedPassword);
        }
    }
}
