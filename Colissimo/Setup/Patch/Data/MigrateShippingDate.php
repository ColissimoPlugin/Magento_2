<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use LaPoste\Colissimo\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class MigrateShippingDate implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private WriterInterface $configWriter;
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
        $cuttOffDates = $this->helperData->getAdvancedConfigValue('lpc_checkout/deliveryDateCuttoffTimes');
        if (!empty($cuttOffDates)) {
            $cuttOffDates = json_decode($cuttOffDates, true);
            if (!empty($cuttOffDates['weekly_schedule'])) {
                foreach ($cuttOffDates['weekly_schedule'] as $index => $cuttOffHour) {
                    $cuttOffDates['weekly_schedule'][$index] = [
                        'cuttOff' => $cuttOffHour,
                        'delay'   => '',
                    ];
                }

                $this->configWriter->save('lpc_advanced/lpc_checkout/deliveryDateCuttoffTimes', json_encode($cuttOffDates), ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }
}
