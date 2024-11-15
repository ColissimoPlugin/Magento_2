<?php
/**
 * ******************************************************
 *  * Copyright (C) 2018 La Poste.
 *  *
 *  * This file is part of La Poste - Colissimo module.
 *  *
 *  * La Poste - Colissimo module can not be copied and/or distributed without the express
 *  * permission of La Poste.
 *  ******************************************************
 *
 */

namespace LaPoste\Colissimo\Controller\Adminhtml\Shipment;

use LaPoste\Colissimo\Block\Adminhtml\Shipment\Label\OutwardLabel;

class MassPrintOutwardLabel extends MassPrintLabels
{

    /**
     * @return string
     * @throws \Exception
     */
    public function getCorrespondingLabelBlock()
    {
        return OutwardLabel::class;
    }

    public function getPdf()
    {
        $pdfData = $this->shipment->getDataUsingMethod('shipping_label');
        if (!empty($pdfData)) {
            $this->pages[] = $pdfData;
        }
    }
}
