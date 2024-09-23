<?php

namespace LaPoste\Colissimo\Model;

use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\Carrier\Colissimo;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ResourceConnection;

class Feedback implements MessageInterface
{
    private UrlInterface $urlBuilder;
    private Data $helperData;
    private ResourceConnection $resourceConnection;

    public function __construct(
        UrlInterface $urlBuilder,
        Data $helperData,
        ResourceConnection $resourceConnection
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->helperData = $helperData;
        $this->resourceConnection = $resourceConnection;
    }

    public function getIdentity(): string
    {
        return 'lpc_feedback';
    }

    public function isDisplayed(): bool
    {
        $deadline = new \DateTime('2025-01-01');
        $now = new \DateTime();

        // if ($now >= $deadline) {
        // return false;
        // }

        $markers = $this->helperData->getMarkers();
        if (!empty($markers['feedback'])) {
            return false;
        }

        // Get number of parcels generated with Colissimo
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('sales_shipment_track');
        $sql = $connection->select()
                          ->from($tableName, ['count(*)'])
                          ->where('track_number IS NOT NULL')
                          ->where('carrier_code = ?', Colissimo::CODE);
        $numberOfParcels = $connection->fetchOne($sql);

        return $numberOfParcels > 10;
    }

    public function getText(): string
    {
        $redirectLinkConfig = $this->urlBuilder->getUrl(
            $this->helperData
                ->getAdminRoute('configuration', 'feedbackredirect')
        );
        $message = '<span style="color:#eb5203; font-weight: bold;">Colissimo</span> - ';
        $message .= __(
            'Would you like to help us improve our module by answering our questionnaire?<br /> If so, go to <a href="%1">the Module feedback part of our configuration page</a>.',
            $redirectLinkConfig
        );

        $currentUrl = $this->urlBuilder->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
        if (strpos($currentUrl, 'isAjax=true') === false) {
            $this->helperData->setMarker('feedback_dismiss', $currentUrl);
        }

        $dismissLink = $this->urlBuilder->getUrl(
            $this->helperData
                ->getAdminRoute('configuration', 'feedbackdismiss')
        );
        $message .= '<a href="' . $dismissLink . '" style="margin-left: 3rem;">';
        $message .= __('If not, don\'t remind me.');
        $message .= '</a>';

        return $message;
    }

    public function getSeverity()
    {
        return self::SEVERITY_MINOR;
    }
}
