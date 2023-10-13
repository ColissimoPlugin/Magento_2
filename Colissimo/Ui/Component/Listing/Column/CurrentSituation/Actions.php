<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Ui\Component\Listing\Column\CurrentSituation;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use LaPoste\Colissimo\Controller\Adminhtml\Shipment\DownloadLabel;

class Actions extends Column
{
    const RETURN_LABEL_LETTER_MARK = 'R';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->prepareItem($item);
            }
        }

        return $dataSource;
    }

    protected function prepareItem($item)
    {
        if (isset($item['entity_id'])) {
            $viewShipmentActionUrl = $this->context->getUrl(
                'sales/shipment/view',
                ['shipment_id' => $item['shipment_entity_id']]
            );

            $viewOrderActionUrl = $this->context->getUrl(
                'sales/order/view',
                ['order_id' => $item['order_entity_id']]
            );

            $trackNumber = $item['track_number'];

            if (self::RETURN_LABEL_LETTER_MARK === substr($trackNumber, 1, 1)) {
                $controllerAction = 'laposte_colissimo/shipment/downloadLabel';
                $labelType = DownloadLabel::LABEL_TYPE_INWARD;
            } else {
                $controllerAction = 'laposte_colissimo/shipment/downloadLabel';
                $labelType = DownloadLabel::LABEL_TYPE_OUTWARD;
            }

            $printLabelActionUrl = $this->context->getUrl(
                $controllerAction,
                [
                    'shipment_id' => $item['shipment_entity_id'],
                    'label_type'  => $labelType,
                ]
            );

            $actions = [
                'view_shipment' => [
                    'href'  => $viewShipmentActionUrl,
                    'label' => __('View Shipment'),
                ],
                'view_order'    => [
                    'href'  => $viewOrderActionUrl,
                    'label' => __('View Order'),
                ],
                'print_label'   => [
                    'href'  => $printLabelActionUrl,
                    'label' => __('Download label'),
                ],
            ];

            return $actions;
        }
    }
}
