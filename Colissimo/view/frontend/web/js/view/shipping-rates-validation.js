/*
 * ******************************************************
 *  * Copyright (C) 2020 La Poste.
 *  *
 *  * This file is part of La Poste - Colissimo module.
 *  *
 *  * La Poste - Colissimo module can not be copied and/or distributed without the express
 *  * permission of La Poste.
 *  ******************************************************
 *
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-rates-validation-rules',
    '../model/shipping-rates-validator',
    '../model/shipping-rates-validation-rules'
], function (Component, defaultShippingRatesValidator, defaultShippingRatesValidationRules, shippingRatesValidator, shippingRatesValidationRules) {
    'use strict';
    defaultShippingRatesValidator.registerValidator('colissimo', shippingRatesValidator);
    defaultShippingRatesValidationRules.registerRules('colissimo', shippingRatesValidationRules);
    return Component;
});
