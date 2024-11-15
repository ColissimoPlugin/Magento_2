require([
    'jquery',
    'lpc'
], function ($, lpc) {
    lpc.setWebserviceRelayUrl(document.getElementById('lpc_pickup_ajax_relays_url').value);
    lpc.setAutoSelectRelay(document.getElementById('lpc_pickup_auto_select').value === 'true');
    lpc.lpcSetMapMarker(document.getElementById('lpc_pickup_marker_url').value);
    lpc.lpcSetAjaxSetRelayInformationUrl(document.getElementById('lpc_pickup_set_pickup_url').value);
});
