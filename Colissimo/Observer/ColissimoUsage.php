<?php

namespace LaPoste\Colissimo\Observer;

use LaPoste\Colissimo\Logger\Colissimo as Logger;
use LaPoste\Colissimo\Model\AccountApi;
use LaPoste\Colissimo\Model\Carrier\Colissimo;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Model\AbstractModel;

class ColissimoUsage implements ObserverInterface
{
    private Logger $logger;
    private AccountApi $accountApi;

    public function __construct(
        Logger $logger,
        AccountApi $accountApi
    ) {
        $this->logger = $logger;
        $this->accountApi = $accountApi;
    }

    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();

            if (
                !($order instanceof AbstractModel)
                || $order->getIsVirtual()
                || Colissimo::CODE !== $order->getShippingMethod(true)->getCarrierCode()
            ) {
                return $this;
            }

            if ($order->canShip()) {
                $this->accountApi->getAccountInformation(true);
            }
        } catch (\Exception $e) {
            $this->logger->error('An error occurred!', ['e' => $e]);
        }

        return $this;
    }
}
