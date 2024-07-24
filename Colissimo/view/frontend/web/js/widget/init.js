require([
    'lpc',
    'jquery',
    'jquery/ui'
], function (lpc, $) {
    window.lpcCallBackFrame = lpc.lpcCallBackFrame;
    lpc.lpcSetWidgetRelayCountries(document.getElementById('lpc_pickup_widget_country').value);

    const widgetScript = document.createElement('script');
    widgetScript.type = 'text/javascript';
    widgetScript.nonce = document.getElementById('lpc_pickup_widget_nonce').value;
    widgetScript.src = 'https://ws.colissimo.fr/widget-colissimo/js/jquery.plugin.colissimo.js';
    $('body').append(widgetScript);
});
