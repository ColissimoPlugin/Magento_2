<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Api\Carrier;

interface GenerateLabelPayload
{
    /**
     * Associate the given Sender to this payload
     *
     * @param $sender  array if null, will use data from the configuration
     * @param $storeId the store to get default information from, if null defaults to current store
     *
     * @return GenerateLabelPayload
     */
    public function withSender(array $sender = null, $storeId = null);

    /**
     * Associate the given CommercialName to this payload
     *
     * @param $commercialName string if null, will use default store name from the configuration
     * @param $storeId        the store to get default information from, if null defaults to current store
     *
     * @return GenerateLabelPayload
     */
    public function withCommercialName($commercialName = null, $storeId = null);

    /**
     * Associate the given credentials to this payload
     *
     * @param $storeId        the store to get default information from, if null defaults to current store
     *
     * @return GenerateLabelPayload
     */
    public function withCredentials($storeId = null);

    /**
     * Associate the given Addressee to this payload
     *
     * @param        $addressee array
     * @param null   $orderRef
     * @param null   $storeId   the store to get default information from, if null defaults to current store
     * @param string $shippingMethodUsed
     * @return GenerateLabelPayload
     */
    public function withAddressee(array $addressee, $orderRef = null, $storeId = null, $shippingMethodUsed = '');

    /**
     * Associate the given PickupLocationId to this payload
     *
     * @return GenerateLabelPayload
     */
    public function withPickupLocationId($pickupLocationId);

    // Service

    /**
     * Associate the given ProductCode to this payload.
     *
     * @return GenerateLabelPayload
     */
    public function withProductCode($productCode);

    /**
     * Associate the given DepositDate to this payload.
     *
     * @return GenerateLabelPayload
     */
    public function withDepositDate(\DateTime $depositDate);

    /**
     * Apply the given preparation delay to this payload.
     *
     * @param $delay   the delay in days
     * @param $storeId the store to get default information from, if null defaults to current store
     *
     * @return GenerateLabelPayload
     */
    public function withPreparationDelay($delay = null, $storeId = null);

    /**
     * Ask for the given output format for the label for this payload.
     *
     * @return GenerateLabelPayload
     */
    public function withOutputFormat($ouptutFormat = null, $storeId = null, $productCode = null);

    /**
     * Associate the given Order id to this payload.
     *
     * @return GenerateLabelPayload
     */
    public function withOrderNumber($orderNumber);

    /**
     * Associate the given Packages to this payload.
     *
     * @return GenerateLabelPayload
     */
    public function withPackage(
        \Magento\Framework\DataObject $package,
        array $items
    );


    // Parcel

    /**
     * Associate the given InsuranceValue to this payload.
     *
     * @return GenerateLabelPayload
     */
    public function withInsuranceValue($amount, $productCode, $countryCode, $shippingMethodUsed, $destinationPostcode, $shipment, $originCountryId = 'fr', $customAmount = null);

    /**
     * Associate the given RecommendationLevel to this payload.
     *
     * @return GenerateLabelPayload
     */
    public function withRecommendationLevel($recommendation);

    /**
     * Associate the given COD to this payload.
     *
     * It will automaticaly makes this payload COD.
     *
     * @return GenerateLabelPayload
     */
    public function withCODAmount($amount);

    /**
     * Flag this payload for ReturnReceipt.
     *
     * @return GenerateLabelPayload
     */
    public function withReturnReceipt($value = true);

    /**
     * Associate the given delivery Instructions to this payload.
     *
     * @return GenerateLabelPayload
     */
    public function withInstructions($instructions);

    /**
     * Associate non-articles information about customs.
     * @see Articles-based customs info are put by withPackage
     */
    public function withCustomsDeclaration(
        \Magento\Sales\Model\Order\Shipment $shipment,
        array $items,
        $destinationCountryId,
        $destinationPostcode,
        $storeId = null,
        $originCountryId = 'fr',
        $shippingType = null
    );

    /**
     * Associates parameters for DDP methods
     * @return GenerateLabelPayload
     */
    public function withDdp($shipment, $shippingMethod, $recipient);

    /**
     * Disable blocking code for signature shipments
     * @return GenerateLabelPayload
     */
    public function withBlockingCode($shippingMethodUsed, $items, $order, $shipment, $postData, $storeId);

    //Fields

    /**
     * Include cuser info text field
     * @return GenerateLabelPayload
     */
    public function withCuserInfoText($info = null);

    /**
     * To know if it is a return label
     *
     * @return boolean
     */
    public function getIsReturnLabel();

    /**
     * Set if it is a return label
     *
     * @return GenerateLabelPayload
     */
    public function isReturnLabel($isReturnLabel = true);

    /**
     * @return GenerateLabelPayload
     */
    public function checkConsistency();


    // Generation

    /**
     * Assemble the complete payload.
     *
     * @return array
     */
    public function assemble();

    /**
     * Reset to empty the payload
     *
     * @return GenerateLabelPayload
     */
    public function resetPayload();

    /**
     * Return payload without password for logging by example
     *
     * @return array
     */
    public function getPayloadWithoutPassword();

    /**
     * Either send with DPD or the postal partner for Austria, Germany, Italy and Luxembourg
     *
     * @return GenerateLabelPayload
     */
    public function withPostalNetwork($countryCode, $productCode, $shippingMethod);
}
