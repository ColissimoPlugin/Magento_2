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

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class TrackNumber extends Column
{
    /**
     * Escaper
     *
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Escaper            $escaper
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        array $components = [],
        array $data = []
    ) {
        $this->escaper = $escaper;
        $this->shipmentRepository = $shipmentRepository;
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
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = $this->prepareItem($item);
                $item['entity_id'] = $item['shipment_entity_id'];
            }
        }

        return $dataSource;
    }

    /**
     * Get data
     *
     * @param array $item
     * @return string
     */
    protected function prepareItem(array $item)
    {
        $shipment = $this->shipmentRepository
            ->get($item['shipment_entity_id']);

        $result = [];

        foreach ($shipment->getAllTracks() as $track) {
            $result[$track->getEntityId()] = $this->escaper->escapeHtml($track->getTrackNumber());
        }

        krsort($result);
        $result = array_values($result);

        // emphasizes last track number
        if (!empty($result[0])) {
            $result[0] = '<big><b>' . $result[0] . '</b></big>';
        }

        return implode(', ', $result);
    }
}
