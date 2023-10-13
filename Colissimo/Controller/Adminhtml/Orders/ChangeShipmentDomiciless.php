<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Orders;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Exception;
use LaPoste\Colissimo\Model\Carrier\Colissimo;

class ChangeShipmentDomiciless extends ChangeShipment
{
    /**
     * Execute action
     *
     * @return Redirect
     * @throws LocalizedException|Exception
     */
    public function execute()
    {
        return $this->executeShipmentModification(Colissimo::CODE_SHIPPING_METHOD_DOMICILE_SS);
    }
}
