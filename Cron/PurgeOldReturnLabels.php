<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Cron;

class PurgeOldReturnLabels
{
    const RETURN_LABEL_LETTER_MARK = 'R';
    const DUMMY_LOCAL_IP = '127.42.0.42';
    const ISO_DATETIME_FORMAT = 'Y-m-d\TH:i:sP';

    protected $helperData;
    protected $logger;
    protected $unifiedTrackingApi;
    protected $shipmentRepository;
    protected $searchCriteriaBuilder;

    public function __construct(
        \LaPoste\Colissimo\Helper\Data $helperData,
        \LaPoste\Colissimo\Logger\Colissimo $logger,
        \LaPoste\Colissimo\Api\UnifiedTrackingApi $unifiedTrackingApi,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->helperData = $helperData;
        $this->logger = $logger;
        $this->unifiedTrackingApi = $unifiedTrackingApi;
        $this->shipmentRepository = $shipmentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function execute()
    {
        $this->logger->info(__METHOD__);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                'lpc_return_label',
                true,
                'notnull'
            )
            ->create();

        $shipments = $this->shipmentRepository
            ->getList($searchCriteria);

        $thresholdDate = new \DateTime();
        $nbDaysForPurge = $this->helperData->getAdvancedConfigValue('lpc_return_labels/nbDaysForPurge');
        $nbDaysForPurge = (int) $nbDaysForPurge;
        if ($nbDaysForPurge > 0) {
            $thresholdDate->sub(new \DateInterval("P{$nbDaysForPurge}D"));
        } else {
            $this->logger->warning(
                'Return label purge was not executed because nbDaysForPurge was negative or zero!',
                ['given' => $nbDaysForPurge]
            );

            return;
        }


        foreach ($shipments as $shipment) {
            $lastTrack = $shipment->getTracksCollection()
                                  ->getLastItem();

            $trackNumber = $lastTrack->getTrackNumber();

            // check that its a return label
            if (self::RETURN_LABEL_LETTER_MARK === substr($trackNumber, 1, 1)) {
                $trackingInfo = $this->unifiedTrackingApi
                    ->getTrackingInfo(
                        $trackNumber,
                        self::DUMMY_LOCAL_IP,
                        null,
                        null,
                        null,
                        $shipment->getStoreId()
                    );

                $lastEvent = end($trackingInfo->parcel->event);
                $eventLastDate = $lastEvent->date;
                $eventLastDate = \DateTime::createFromFormat(
                    self::ISO_DATETIME_FORMAT,
                    $eventLastDate
                );

                $statusDelivery = isset($trackingInfo->parcel->statusDelivery) && $trackingInfo->parcel->statusDelivery;

                if ($statusDelivery) {
                    if ($thresholdDate->getTimestamp() >= $eventLastDate->getTimestamp()) {
                        $this->logger->info(
                            __METHOD__,
                            [
                                'trackingNumber' => $trackNumber,
                                'shipmentId'     => $shipment->getEntityId(),
                                'action'         => 'purging return label blob',
                            ]
                        );

                        $shipment->setLpcReturnLabel(null);
                        $this->shipmentRepository
                            ->save($shipment);
                    } else {
                        $this->logger->debug(
                            __METHOD__,
                            [
                                'trackingNumber' => $trackNumber,
                                'shipmentId'     => $shipment->getEntityId(),
                                'action'         => 'none',
                                'cause'          => 'last event not old enough',
                            ]
                        );
                    }
                } else {
                    $this->logger->debug(
                        __METHOD__,
                        [
                            'trackingNumber' => $trackNumber,
                            'shipmentId'     => $shipment->getEntityId(),
                            'action'         => 'none',
                            'cause'          => 'status is not delivered',
                        ]
                    );
                }
            }
        }

        return $this;
    }
}
