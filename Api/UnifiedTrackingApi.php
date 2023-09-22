<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Api;

interface UnifiedTrackingApi
{
    /**
     * Retrieve the current state of the tracking for a parcel.
     *
     * This will return a stdObj with the following properties:
     *   - error
     *     * code (should be 0 if success)
     *     * message
     *   - message
     *     * message
     *   - parcel
     *     * deliveryTypeLabel
     *     * event
     *       - date
     *       - label
     *       - siteCity
     *   - parcelNumber
     *
     * @param $trackingNumber string
     * @param $ip             string
     * @param $lang           string
     * @param $login          if null, retrieve it from the configuration
     * @param $password       if null, retrieve it from the configuration
     * @param $storeId
     *
     * Will throw \SoapFault if the Soap protocol fails.
     * Will throw \LaPoste\Colissimo\Exception\TrackingApiException if error.code was not 0.
     */
    public function getTrackingInfo(
        $trackingNumber,
        $ip,
        $lang = null,
        $login = null,
        $password = null,
        $storeId = null
    );

    /**
     * Retrieve the current state of all the Colissimo parcel currently tracked.
     *
     * @param      $login    if null, retrieve it from the configuration
     * @param      $password if null, retrieve it from the configuration
     * @param null $ip
     * @param null $lang
     *
     * Will throw \LaPoste\Colissimo\Exception\ApiException if credential are not OK.
     * @return
     */
    public function updateAllStatuses($login = null, $password = null, $ip = null, $lang = null);

    /**
     * Encrypt the track_number for usage in the frontend (like for URL)
     */
    public function encrypt($trackNumber);

    /**
     * Decrypt a previously encrypted track_number.
     */
    public function decrypt($trackHash);
}
