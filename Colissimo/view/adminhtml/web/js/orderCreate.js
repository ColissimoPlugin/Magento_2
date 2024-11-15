require([
    'jquery',
    'lpc',
    'Magento_Ui/js/modal/modal',
    'jquery/ui',
    'leaflet'
], function ($, lpc, modal) {
    const lpcRelayMode = $('#lpc_relay_mode');
    if (lpcRelayMode.length === 0) {
        return;
    }

    lpc.setWebserviceRelayUrl($('#lpc_webservice_relay_url').val());
    lpc.setAutoSelectRelay($('#lpc_auto_select_relay').val());

    // Wait for the data edit step to be visible before initializing the pickup choosing button
    const lpcInitPickupButton = setInterval(function () {
        if ($('#order-data:visible').length) {
            clearInterval(lpcInitPickupButton);

            $(document).on('click', '[name="order[shipping_method]"]', function () {
                lpcShowHideButton(this.value);
            });

            lpcShowHideButton($('[name="order[shipping_method]"]:checked').val());
        }
    }, 100);

    // Creates a section containing the pickup choosing button
    function addLpcSection() {
        if ($('#order-lpc-pickup').length) {
            return;
        }

        const lpcChooseRelayButton = $('#lpc_choose_relay_button_template').html();
        $(lpcChooseRelayButton).insertAfter($('#order-methods'));

        $('#lpc_change_my_relay').on('click', function () {
            lpc.lpcOpenPopupAndMap(modal);
        });

        if (lpc.getAutoSelectRelay()) {
            lpc.lpcLoadRelaysList(false);
        }
    }

    // Shows or hides the pickup choosing button based on the selected shipping method
    function lpcShowHideButton(value) {
        if (value === $('#lpc_pickup_carrier_code').val()) {
            addLpcSection();
            $('#order-lpc-pickup').show();
        } else {
            $('#order-lpc-pickup').hide();
        }
    }

    if ('gmaps' === lpcRelayMode.val()) {
        lpc.lpcSetMapMarker($('#lpc_map_marker').val());
        if (typeof google !== 'undefined') {
            lpc.lpcSetMapType('gmaps');
            lpc.lpcLoadMap();
            lpc.intiSwitchMobileLayout();
        } else {
            console.error(
                'Google is not defined. Please check if an API key is set in the configuration (Stores->Configuration->Sales->La Poste Colissimo Advanced Setup)');
        }

        lpc.lpcSetAjaxSetRelayInformationUrl($('#lpc_ajax_set_information_relay_url').val());
        $('#lpc_layer_button_search').on('click', function () {
            lpc.lpcLoadRelaysList(true);
        });
        $('#lpc_modal_relays_display_more').children('a').on('click', function (e) {
            lpc.lpcLoadRelaysList(true, true);
            e.preventDefault();
        });
    } else if ('leaflet' === lpcRelayMode.val()) {
        lpc.lpcSetMapMarker($('#lpc_map_marker').val());
        lpc.lpcSetMapType('leaflet');
        lpc.lpcLoadMap();
        lpc.intiSwitchMobileLayout();

        lpc.lpcSetAjaxSetRelayInformationUrl($('#lpc_ajax_set_information_relay_url').val());
        $('#lpc_layer_button_search').on('click', function () {
            lpc.lpcLoadRelaysList(true);
        });
        $('#lpc_modal_relays_display_more').children('a').on('click', function (e) {
            lpc.lpcLoadRelaysList(true, true);
            e.preventDefault();
        });
    } else if('widget' === lpcRelayMode.val()) {
        lpc.lpcSetMapMarker($('#lpc_map_marker').val());
        window.lpcCallBackFrame = lpc.lpcCallBackFrame;
        lpc.lpcSetAjaxSetRelayInformationUrl($('#lpc_ajax_set_information_relay_url').val());
        lpc.lpcSetWidgetRelayCountries($('#lpc_widget_list_country').val());

        const mapboxScript = document.createElement('script');
        mapboxScript.type = 'text/javascript';
        mapboxScript.src = $('#lpc_mapbox_script').val();
        $('body').append(mapboxScript);

        const widgetScript = document.createElement('script');
        widgetScript.type = 'text/javascript';
        widgetScript.src = 'https://ws.colissimo.fr/widget-colissimo/js/jquery.plugin.colissimo.js';
        $('body').append(widgetScript);
    }
});
