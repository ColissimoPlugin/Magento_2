require([
    'lpc',
    'jquery',
    'jquery/ui',
    'jquery.plugin.colissimo'
], function (lpc) {
    const countryInput = document.getElementById('lpc_pickup_widget_country');

    if (countryInput) {
        window.lpcCallBackFrame = lpc.lpcCallBackFrame;
        lpc.lpcSetWidgetRelayCountries(countryInput.value);
    }
});
