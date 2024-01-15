define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_CheckoutAgreements/js/model/agreements-assigner',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/url-builder',
    'mage/url',
    'Magento_Checkout/js/model/error-processor',
    'uiRegistry'
], function ($, wrapper, agreementsAssigner, quote, customer, urlBuilder, urlFormatter, errorProcessor, registry) {
    'use strict';

    return function (placeOrderAction) {
        /** Override default place order action and add agreement_ids to request */
        return wrapper.wrap(placeOrderAction, function (originalAction, paymentData, messageContainer) {
            agreementsAssigner(paymentData);
            const isCustomer = customer.isLoggedIn();
            const quoteId = quote.getQuoteId();
            const url = urlFormatter.build('lpc/quote/save');
            const lpcShippingNote = $('[name="lpc_shipping_note"]').val();

            if (lpcShippingNote) {

                const payload = {
                    'cartId': quoteId,
                    'lpc_shipping_note': lpcShippingNote,
                    'is_customer': isCustomer
                };

                if (!payload.lpc_shipping_note) {
                    return true;
                }

                var result = true;

                $.ajax({
                    url: url,
                    data: payload,
                    dataType: 'text',
                    type: 'POST'
                }).done(function (response) {
                    result = true;
                }).fail(function (response) {
                    result = false;
                    errorProcessor.process(response);
                });
            }

            return originalAction(paymentData, messageContainer);
        });
    };
});
