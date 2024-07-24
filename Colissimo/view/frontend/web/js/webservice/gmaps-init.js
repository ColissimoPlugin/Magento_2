require([
    'jquery',
    'lpc'
], function ($, lpc) {
    if (typeof google !== 'undefined') {
        lpc.lpcSetMapType('gmaps');
        lpc.lpcLoadMap();
        lpc.intiSwitchMobileLayout();
    } else {
        console.error(
            'Google is not defined. Please check if an API key is set in the configuration (Stores->Configuration->Sales->La Poste Colissimo Advanced Setup)');
    }

    $('#lpc_layer_button_search').on('click', function () {
        lpc.lpcLoadRelaysList(true);
    });
    $('#lpc_modal_relays_display_more').children('a').on('click', function (e) {
        lpc.lpcLoadRelaysList(true, true);
        e.preventDefault();
    });
});
