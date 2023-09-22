<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use \Magento\Framework\App\Config\Storage\WriterInterface;
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
     * @var \LaPoste\Colissimo\Helper\Data
     */
    private $helperData;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface          $configWriter
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter,
        \LaPoste\Colissimo\Helper\Data $helperData
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
        $this->moduleDataSetup->getConnection()->startSetup();

        $countryCodes = ['AT', 'DE', 'IT', 'LU'];

        $domicileasSendingService = $this->helperData->getConfigValue('carriers/lpc_group/domicileas_sendingservice');
        $expertSendingService = $this->helperData->getConfigValue('carriers/lpc_group/expert_sendingservice');

        foreach ($countryCodes as $countryCode) {
            $this->configWriter->save('carriers/lpc_group/domicileas_sendingservice_' . $countryCode,
                                      $domicileasSendingService,
                                      $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                                      $scopeId = 0);
            $this->configWriter->save('carriers/lpc_group/expert_sendingservice_' . $countryCode,
                                      $expertSendingService,
                                      $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                                      $scopeId = 0);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }
}
