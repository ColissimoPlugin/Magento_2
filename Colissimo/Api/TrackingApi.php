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

interface TrackingApi
{
    /**
     * Retrieve the current state of the tracking for a parcel.
     *
     * This will return a stdObj with the following properties:
     *   - errorCode (should be 0 if success)
     *   - eventCode
     *   - eventDate
     *   - eventLibelle
     *   - recipientCity
     *   - recipientCountryCode
     *   - recipientZipCode
     *   - skybillNumber
     *
     * @param $trackingNumber string
     * @param $login          if null, retrieve it from the configuration
     * @param $password       if null, retrieve it from the configuration
     * @param $storeId
     *
     * Will throw \SoapFault if the Soap protocol fails.
     * Will throw \LaPoste\Colissimo\Exception\TrackingApiException if errorCode was not 0.
     */
    public function getCurrentState($trackingNumber, $login = null, $password = null, $storeId = null);

    /**
     * Retrieve the current state of all the Colissimo parcel currently tracked.
     *
     * @param $login    if null, retrieve it from the configuration
     * @param $password if null, retrieve it from the configuration
     *
     * Will throw \LaPoste\Colissimo\Exception\ApiException if credential are not OK.
     */
    public function updateAllStatuses($login = null, $password = null);

    /**
     * Encrypt the track_number for usage in the frontend (like for URL)
     */
    public function encrypt($trackNumber);

    /**
     * Decrypt a previously encrypted track_number.
     */
    public function decrypt($trackHash);
}
