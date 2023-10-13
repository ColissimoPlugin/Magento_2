<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/


namespace LaPoste\Colissimo\Controller\Adminhtml\Shipment;


interface PrintLabelInterface
{
    /**
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function getShipment();
}