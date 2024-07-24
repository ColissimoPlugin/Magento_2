require([
    'jquery',
    'leaflet',
    'lpc'
], function ($, leaflet, lpc) {
    document.getElementById('lpc_pickup_marker');
    lpc.lpcSetMapType('leaflet');
    lpc.lpcLoadMap();
    lpc.intiSwitchMobileLayout();

    $('#lpc_layer_button_search').on('click', function () {
        lpc.lpcLoadRelaysList(true);
    });
    $('#lpc_modal_relays_display_more').children('a').on('click', function (e) {
        lpc.lpcLoadRelaysList(true, true);
        e.preventDefault();
    });
});
