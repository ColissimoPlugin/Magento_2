define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'lpc'
], function ($, wrapper, quote, lpc) {

    return function (setShippingInformationAction) {

        function isLpcRelay() {
            return quote.shippingMethod() !== null && quote.shippingMethod().method_code == 'pr' && quote.shippingMethod().carrier_code == 'colissimo';
        }

        return wrapper.wrap(setShippingInformationAction, function (originalAction) {
            if (isLpcRelay()) {
                var shippingAddress = quote.shippingAddress();
                if (shippingAddress) {
                    shippingAddress['company'] = lpc.lpcGetRelayName();
                    shippingAddress['city'] = lpc.lpcGetRelayCity();
                    shippingAddress['street'] = [];
                    shippingAddress['street'][0] = lpc.lpcGetRelayAddress();
                    shippingAddress['postcode'] = lpc.lpcGetRelayZipcode();
                    shippingAddress['countryId'] = lpc.lpcGetRelayCountry();
                }
            }

            return originalAction();
        });
    };
});
