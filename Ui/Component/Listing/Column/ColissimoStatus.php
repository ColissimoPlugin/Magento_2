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

use LaPoste\Colissimo\Api\UnifiedTrackingApi;
use Magento\Framework\Escaper;
use Magento\Framework\Url;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Sales\Api\ShipmentRepositoryInterface;

class ColissimoStatus extends Column
{
    /**
     * Escaper
     *
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    protected $urlHelper;

    protected $colisimoStatus;

    /**
     * @var LaPoste\Colissimo\Api\UnifiedTrackingApi
     */
    private $unifiedTrackingApi;
    private ShipmentRepositoryInterface $shipmentRepository;

    /**
     * @param ContextInterface                       $context
     * @param UiComponentFactory                     $uiComponentFactory
     * @param Escaper                                $escaper
     * @param Url                                    $urlHelper
     * @param \LaPoste\Colissimo\Api\ColissimoStatus $colissimoStatus
     * @param UnifiedTrackingApi                     $unifiedTrackingApi
     * @param array                                  $components
     * @param array                                  $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        Url $urlHelper,
        \LaPoste\Colissimo\Api\ColissimoStatus $colissimoStatus,
        UnifiedTrackingApi $unifiedTrackingApi,
        ShipmentRepositoryInterface $shipmentRepository,
        array $components = [],
        array $data = []
    ) {
        $this->escaper = $escaper;
        $this->urlHelper = $urlHelper;
        $this->colissimoStatus = $colissimoStatus;
        $this->unifiedTrackingApi = $unifiedTrackingApi;
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
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->prepareItem($item);
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
        $shipmentStatus = $item['shipment_status'];

        $result = '-';
        if (!empty($shipmentStatus)) {
            $statusInfo = $this->colissimoStatus->getStatusInfo($shipmentStatus);

            if (null === $statusInfo) {
                $result = __('Unknown status: %1', $shipmentStatus);
            } else {
                $result = $this->escaper->escapeHtml($statusInfo['label']);

                // adds warning picture to status in error
                if ($statusInfo['is_anomaly'] && !empty($statusInfo['label'])) {
                    $result = <<<END_HTML
<img src="/static/adminhtml/Magento/backend/en_US/images/rule_component_remove.gif"> $result
END_HTML;
                }
            }
        }

        $trackhash = $this->unifiedTrackingApi
            ->encrypt($item['entity_id']);

        $trackingActionUrl = $this->urlHelper->getUrl(
            'lpc/tracking',
            ['trackhash' => $trackhash]
        );

        $statusHtml = '<a target="_blank" href="' . $trackingActionUrl . '">' . $result . '</a>';

        $shipment = $this->shipmentRepository->get($item['shipment_entity_id']);
        $currentMessages = $shipment->getDataUsingMethod('lpc_label_error');
        if (empty($currentMessages)) {
            return $statusHtml;
        }

        $currentMessages = json_decode($currentMessages, \JSON_OBJECT_AS_ARRAY);
        if (empty($currentMessages)) {
            return $statusHtml;
        }

        $typesLabels = [
            'outward' => __('Error generating outward label:'),
            'inward'  => __('Error generating inward label:'),
        ];

        foreach ($currentMessages as $type => $oneMessage) {
            $statusHtml .= '<br/><span style="color: red;">' . $typesLabels[$type] . ' ' . $oneMessage . '</span>';
        }

        return $statusHtml;
    }
}
