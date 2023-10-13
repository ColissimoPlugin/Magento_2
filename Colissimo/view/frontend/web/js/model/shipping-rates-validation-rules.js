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

define([], function () {
    'use strict';
    return {
        getRules: function () {
            return {
                'postcode': {
                    'required': true
                },
                'country_id': {
                    'required': true
                },
                'city': {
                    'required': true
                }
            };
        }
    };
});
