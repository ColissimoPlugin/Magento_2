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

use LaPoste\Colissimo\Api\ColissimoStatus;
use Magento\Framework\Escaper;
use Magento\Framework\Url;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class IsAnomaly extends Column
{
    const SHIPPING_DELAY_WARNING = '-72 hours';

    /**
     * Escaper
     *
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \Magento\Framework\Url
     */
    protected $urlHelper;

    /**
     * @var \LaPoste\Colissimo\Api\ColissimoStatus
     */
    protected $colissimoStatus;

    /**
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Escaper            $escaper
     * @param Url                $urlHelper
     * @param ColissimoStatus    $colissimoStatus
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        Url $urlHelper,
        ColissimoStatus $colissimoStatus,
        array $components = [],
        array $data = []
    ) {
        $this->escaper = $escaper;
        $this->urlHelper = $urlHelper;
        $this->colissimoStatus = $colissimoStatus;
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
        $result = '';
        $lpcClass = '';
        if (!empty($item['lpc_last_event_code'])) {
            $shipmentStatusCode = $item['lpc_last_event_code'];

            $statusIndex = $this->colissimoStatus->getInternalCodeForClp($shipmentStatusCode);
            $statusInfo = $this->colissimoStatus->getStatusInfo($statusIndex);

            if (null !== $statusInfo && !empty($statusInfo['clp'])) {
                $codeStatus = $this->escaper->escapeHtml($statusInfo['clp']);
                $description = $this->escaper->escapeHtml(__($statusInfo['label']));
                $typo = $statusInfo['typo'];

                $text = '';

                // Check if order handled by La Poste since more than 3 days (and no event since) and not delivered
                if ($typo == 'PCH' && $item['lpc_is_delivered'] == 0) {
                    $dateLimit = new \DateTime(date('Y-m-d H:i:s', strtotime(self::SHIPPING_DELAY_WARNING)));
                    $dateStatus = new \DateTime($item['lpc_last_event_date']);
                    if (!empty($item['lpc_last_event_date']) && $dateStatus < $dateLimit) {
                        $text .= __('Longer than 3 days') . ' ';
                        $lpcClass .= 'lpc_anomaly_icon lpc_anomaly_delay';
                    } else {
                        $text .= __('In transit') . ' ';
                    }
                }
                // Adds warning picture to status in error
                if ($statusInfo['is_anomaly']) {
                    $text .= __('Anomaly');
                    $lpcClass .= "lpc_anomaly_icon lpc_anomaly_status";

                    $result = <<<END_HTML
<div title="$description" class="$lpcClass" aria-hidden="true">$text ($codeStatus)</div>
END_HTML;
                } elseif ('PCHMQT' === $codeStatus) {
                    $text .= __('To ship');

                    $result = <<<END_HTML
<div title="$description" class="$lpcClass" aria-hidden="true">$text ($codeStatus)</div>
END_HTML;
                } else {
                    $text = empty($text) ? __('Unknow status') : $text;
                    $result = <<<END_HTML
<div title="$description" class="$lpcClass" aria-hidden="true">$text ($codeStatus)</div>
END_HTML;
                }
            } else {
                $text = __('Unknow status');
                $result = <<<END_HTML
$text
END_HTML;
            }
        } else {
            $result = __('No status');
        }

        return $result;
    }
}
