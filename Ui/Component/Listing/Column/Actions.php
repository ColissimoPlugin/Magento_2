<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Ui\Component\Listing\Column;

use LaPoste\Colissimo\Controller\Adminhtml\Shipment\PrintLabel;
use LaPoste\Colissimo\Controller\Adminhtml\Shipment\DownloadLabel;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{

    /**
     * Constructor
     *
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
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

            $printLabelActionUrl = $this->context->getUrl(
                'laposte_colissimo/shipment/printLabel',
                [
                    'shipment_id' => $item['shipment_entity_id'],
                    'label_type'  => PrintLabel::LABEL_TYPE_OUTWARD,
                ]
            );

            $printReturnLabelActionUrl = $this->context->getUrl(
                'laposte_colissimo/shipment/printLabel',
                [
                    'shipment_id' => $item['shipment_entity_id'],
                    'label_type'  => PrintLabel::LABEL_TYPE_INWARD,
                ]
            );
            $downloadLabelActionUrl = $this->context->getUrl(
                'laposte_colissimo/shipment/downloadLabel',
                [
                    'shipment_id' => $item['shipment_entity_id'],
                    'label_type'  => DownloadLabel::LABEL_TYPE_OUTWARD,
                ]
            );

            $downloadReturnLabelActionUrl = $this->context->getUrl(
                'laposte_colissimo/shipment/downloadLabel',
                [
                    'shipment_id' => $item['shipment_entity_id'],
                    'label_type'  => DownloadLabel::LABEL_TYPE_INWARD,
                ]
            );

            $sendReturnLabelEmailActionUrl = $this->context->getUrl(
                'laposte_colissimo/shipment/emailReturnLabel',
                ['shipment_id' => $item['shipment_entity_id']]
            );

            $actions = [
                'view'                   => [
                    'href'  => $viewShipmentActionUrl,
                    'label' => __('View'),
                ],
                'print_outward_label'    => [
                    'href'  => $printLabelActionUrl,
                    'label' => __('Print outward label'),
                ],
                'print_return_label'     => [
                    'href'  => $printReturnLabelActionUrl,
                    'label' => __('Print inward label'),
                ],
                'download_outward_label' => [
                    'href'  => $downloadLabelActionUrl,
                    'label' => __('Download outward label'),
                ],
                'download_return_label'  => [
                    'href'  => $downloadReturnLabelActionUrl,
                    'label' => __('Download inward label'),
                ],
                'email_return_label'     => [
                    'href'  => $sendReturnLabelEmailActionUrl,
                    'label' => __('Email inward label'),
                ],
            ];

            return $actions;
        }
    }
}
