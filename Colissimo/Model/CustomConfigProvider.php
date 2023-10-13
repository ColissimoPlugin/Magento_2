<?php

namespace LaPoste\Colissimo\Model;

use LaPoste\Colissimo\Helper\Data;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository;

class CustomConfigProvider implements ConfigProviderInterface
{
    protected $assetRepository;
    protected $helperData;

    public function __construct(Repository $assetRepository, Data $helperData)
    {
        $this->assetRepository = $assetRepository;
        $this->helperData = $helperData;
    }

    public function getConfig()
    {
        $displayLogo = $this->helperData->getConfigValue('carriers/lpc_group/display_logo');
        $colissimoIconUrl = '';
        if ('1' === $displayLogo) {
            $colissimoIconUrl = $this->assetRepository->getUrl('LaPoste_Colissimo::images/colissimo_icon.png');
        }

        return ['colissimoIconUrl' => $colissimoIconUrl];
    }
}
