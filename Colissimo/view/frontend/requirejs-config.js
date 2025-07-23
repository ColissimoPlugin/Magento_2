var config = {
    map: {
        '*': {
            lpc: 'LaPoste_Colissimo/js/lpc',
            leaflet: 'LaPoste_Colissimo/js/webservice/leaflet'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/shipping': {'LaPoste_Colissimo/js/view/shipping': true},
            'Magento_Checkout/js/action/set-shipping-information': {'LaPoste_Colissimo/js/action/set-shipping-information': true},
            'Magento_Checkout/js/action/place-order': {'LaPoste_Colissimo/js/action/place-order': true}
        }
    },
    paths: {
        'jquery.plugin.colissimo': 'https://ws.colissimo.fr/widget-colissimo/js/jquery.plugin.colissimo'
    },
    shim: {
        'jquery.plugin.colissimo': {
            deps: ['jquery']
        }
    }
};
