<?php

namespace LaPoste\Colissimo\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use LaPoste\Colissimo\Helper\Data;

class ConfigChange implements ObserverInterface
{
    private RequestInterface $request;
    private WriterInterface $configWriter;
    private Data $helperData;

    /**
     * @param RequestInterface $request
     * @param WriterInterface  $configWriter
     * @param Data             $helperData
     */
    public function __construct(
        RequestInterface $request,
        WriterInterface $configWriter,
        Data $helperData
    ) {
        $this->request = $request;
        $this->configWriter = $configWriter;
        $this->helperData = $helperData;
    }

    public function execute(Observer $observer): ConfigChange
    {
        $submittedValues = $this->request->getParam('groups');
        $newPassword = $submittedValues['lpc_general']['fields']['pwd_webservices']['value'];
        $encodedNewPassword = base64_encode($newPassword);

        $markers = $this->helperData->getMarkers();
        if (!empty($markers['lpc_encoded_password']) && $markers['lpc_encoded_password'] === $newPassword) {
            return $this;
        }

        $this->configWriter->save('lpc_advanced/lpc_general/pwd_webservices', $encodedNewPassword);
        $this->helperData->setMarker('lpc_encoded_password', $encodedNewPassword);

        return $this;
    }
}
