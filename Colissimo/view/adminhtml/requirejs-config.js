var config = {
    map: {
        '*': {
            lpcScan: 'LaPoste_Colissimo/js/lpcScan',
            lpc: 'LaPoste_Colissimo/js/lpc',
            leaflet: 'LaPoste_Colissimo/js/webservice/leaflet'
        }
    },
    config: {
        mixins: {
            'Magento_Ui/js/form/element/select': {
                'LaPoste_Colissimo/js/form/element/select': true
            }
        }
    }
};
