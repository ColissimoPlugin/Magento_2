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
    'jquery',
    'mageUtils',
    './shipping-rates-validation-rules',
    'mage/translate'
], function ($, utils, validationRules, $t) {
    'use strict';
    return {
        validationErrors: [],
        validate: function (address) {
            var self = this;
            this.validationErrors = [];
            $.each(validationRules.getRules(), function (field, rule) {
                if (rule.required && utils.isEmpty(address[field])) {
                    var message = $t('Field ') + field + $t(' is required.');
                    self.validationErrors.push(message);
                }
            });
            return !Boolean(this.validationErrors.length);
        }
    };
});
