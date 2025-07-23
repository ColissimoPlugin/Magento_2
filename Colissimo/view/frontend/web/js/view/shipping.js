define([
    'jquery',
    'lpc',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/quote'
], function ($, lpc, modal, quote) {
    'use strict';
    let pickupAutoSelectInit;

    const mixin = {
        selectShippingMethod: function (shippingMethod) {
            // Dispay Colissimo shipping information input
            if ('colissimo' === shippingMethod.carrier_code) {
                $('#opc-shipping_method [name=\'shippingAddress.lpc_shipping_note\']').css('display', 'block');
            } else {
                $('#opc-shipping_method [name=\'shippingAddress.lpc_shipping_note\']').css('display', 'none');
            }

            // Display Colissimo relay point selection
            if ('pr' === shippingMethod.method_code && !$('#lpc_chosen_relay').length) {
                $('<div>').attr('id', 'lpc_chosen_relay').appendTo('#label_method_pr_colissimo');
                $('<a>').attr('id', 'lpc_change_my_relay').text($.mage.__('Choose my relay')).appendTo('#label_method_pr_colissimo');
                $('#lpc_change_my_relay').on('click', function () {
                    lpc.lpcOpenPopupAndMap(shippingMethod, modal, quote);
                });

                if (lpc.getAutoSelectRelay()) {
                    lpc.lpcLoadRelaysList(false);
                }
            }

            return this._super();
        },

        setShippingInformation: function () {
            if (this.validateShippingInformation() && this.lpcValidateChoiceRelay()) {
                this._super();
            }
        },

        lpcValidateChoiceRelay: function () {
            if (this.isShippingMethodRelayPoint()) {
                if (!lpc.lpcGetRelayId()) {
                    this.errorValidationMessage($.mage.__('Please choose a relay for this shipping method'));
                    return false;
                }

                var shippingAddress = quote.shippingAddress();
                if (!shippingAddress.telephone || shippingAddress.telephone == undefined || shippingAddress.telephone.length === 0) {
                    this.errorValidationMessage($.mage.__('Please define a mobile phone number for SMS notification tracking'));
                    return false;
                }

                if (shippingAddress.countryId === 'BE') {
                    var acceptableNumber = true;

                    if (!shippingAddress.telephone.match(/^\+324\d{8}$/)) {
                        acceptableNumber = false;
                    } else {
                        var mobileNumbers = shippingAddress.telephone.split('').reverse();
                        var suiteAsc = true;
                        var suiteDesc = true;
                        var suiteEqual = true;
                        for (var i = 0 ; i < mobileNumbers.length ; i++) {
                            if (7 === i) {
                                break;
                            }

                            if (parseInt(mobileNumbers[i + 1]) !== parseInt(mobileNumbers[i]) - 1) {
                                suiteAsc = false;
                            }
                            if (parseInt(mobileNumbers[i + 1]) !== parseInt(mobileNumbers[i]) + 1) {
                                suiteDesc = false;
                            }
                            if (parseInt(mobileNumbers[i + 1]) !== parseInt(mobileNumbers[i])) {
                                suiteEqual = false;
                            }
                        }

                        acceptableNumber = !suiteAsc && !suiteDesc && !suiteEqual;
                    }

                    if (!acceptableNumber) {
                        this.errorValidationMessage($.mage.__(
                            'The mobile number for a Belgian destination must start with +324 and be 12 characters long. For example +324XXXXXXXX'));
                        return false;
                    }
                }
            }

            lpc.lpcPublicSetRelayId('');
            return true;
        },

        isShippingMethodRelayPoint: function () {
            return quote.shippingMethod().carrier_code === 'colissimo' && quote.shippingMethod().method_code.indexOf('pr') !== -1;
        },

        /**
         * @return {Boolean}
         */
        validateShippingInformation: function () {
            var result = this._super();

            if (this.isShippingMethodRelayPoint()) {
                var shippingAddress = quote.shippingAddress();
                shippingAddress['save_in_address_book'] = 0;
            }

            return result;
        },

        getImage: function (carrierCode) {
            this.initAutoSelectPickup();

            if ('colissimo' !== carrierCode || '' == window.checkoutConfig.colissimoIconUrl) {
                return '';
            }

            return '<img alt="Logo Colissimo" src="' + window.checkoutConfig.colissimoIconUrl + '" width="40" class="lpc_method_icon">';
        },

        initAutoSelectPickup: function () {
            clearTimeout(pickupAutoSelectInit);
            pickupAutoSelectInit = setTimeout(function () {
                const pickupRadio = $('input[type="radio"][value="colissimo_pr"]:checked');
                if (pickupRadio.length > 0) {
                    pickupRadio.trigger('click');
                }
            }, 100);
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
