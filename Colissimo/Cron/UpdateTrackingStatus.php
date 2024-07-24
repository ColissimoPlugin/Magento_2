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

use LaPoste\Colissimo\Api\UnifiedTrackingApi;
use LaPoste\Colissimo\Logger\Colissimo;

class UpdateTrackingStatus
{
    const DUMMY_LOCAL_IP = '127.42.0.42';

    /**
     * @var \LaPoste\Colissimo\Logger\Colissimo
     */
    protected $logger;
    /**
     * @var \LaPoste\Colissimo\Api\UnifiedTrackingApi
     */
    protected $unifiedTrackingApi;

    /**
     * UpdateTrackingStatus constructor.
     * @param \LaPoste\Colissimo\Logger\Colissimo       $logger
     * @param \LaPoste\Colissimo\Api\UnifiedTrackingApi $unifiedTrackingApi
     */
    public function __construct(
        Colissimo $logger,
        UnifiedTrackingApi $unifiedTrackingApi
    ) {
        $this->logger = $logger;
        $this->unifiedTrackingApi = $unifiedTrackingApi;
    }

    public function execute()
    {
        $this->logger->info(__METHOD__);

        $result = $this->unifiedTrackingApi->updateAllStatuses(self::DUMMY_LOCAL_IP, null);
        $failure = $result['failure'];

        if (empty($failure)) {
            $this->logger->info(__('All statuses where updated'));
        } else {
            $this->logger->info(__('Some of the status where not correctly updated'));
        }

        return $this;
    }
}
