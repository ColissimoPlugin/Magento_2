<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\Carrier\GenerateLabelPayload;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class MigrateSendingService implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var Data
     */
    private $helperData;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface          $configWriter
     * @param Data                     $helperData
     */
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
        $domicileasSendingService = $this->helperData->getConfigValue('carriers/lpc_group/domicileas_sendingservice');

        foreach (GenerateLabelPayload::COUNTRIES_WITH_PARTNER_SHIPPING as $countryCode) {
            $this->configWriter->save('carriers/lpc_group/domicileas_sendingservice_' . $countryCode, $domicileasSendingService, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }
}
