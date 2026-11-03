require([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'jquery.plugin.colissimo.pdd'
], function ($, modal) {
    let dropOffPopup;

    $('#lpc_drop_off_open').on('click', function () {
        if (!dropOffPopup) {
            dropOffPopup = modal({
                buttons: [],
                responsive: true,
                wrapperClass: 'modals-wrapper lpc_modals-wrapper',
                modalVisibleClass: '_show _show_lpc_drop_off',
                closed: function () {
                    try {
                        $('#lpc_drop_off_widget').frameColissimoClose();
                    } catch (e) {
                    }
                }
            }, $('#lpc_drop_off_layer'));
        }

        dropOffPopup.openModal();
        $('#lpc_drop_off_widget').frameColissimoOpen(JSON.parse($('#lpc_drop_off_widget_options').val()));
    });

    $('#lpcreturn_header_selectall').on('click', function () {
        $('.lpcreturn_selection').prop('checked', this.checked);
    });

    $('#lpcreturn_download').on('click', function () {
        const productIds = [];
        let i = 0;
        $('.lpcreturn_selection:checked').each(function () {
            const productId = $(this).val();
            productIds[i] = productId + '_' + $(`.lpcreturn_quantity[data-product-id="${productId}"]`).val();
            i++;
        });

        if (0 === productIds.length) {
            alert($.mage.__('Please select at least one product.'));
            return;
        }

        const returnLabelDownloadLink = $('#returnLabelDownloadUrl').val();
        const lpcOrderId = $('#lpcOrderId').val();

        $.ajax({
            url: returnLabelDownloadLink,
            type: 'POST',
            dataType: 'json',
            data: {
                orderId: lpcOrderId,
                productIds: JSON.stringify(productIds),
                ajax: 1
            },
            success: function (response) {
                if (response.success) {
                    $('#lpc_products_selection').hide();
                    $('#lpc_return_label_confirmation_tracking_number').text(response.trackingNumber);
                    $('#lpc_return_label_confirmation').show();
                    $('#lpc_return_instructions_container').show();
                    window.location.href = returnLabelDownloadLink + '?orderId=' + lpcOrderId + '&shipmentId=' + response.shipmentId;
                } else {
                    alert(response.error);
                }
            }
        });
    });

    $('#lpcbalreturn').on('click', function () {
        const productIds = [];
        let i = 0;
        $('.lpcreturn_selection:checked').each(function () {
            const productId = $(this).val();
            productIds[i] = productId + '_' + $(`.lpcreturn_quantity[data-product-id="${productId}"]`).val();
            i++;
        });

        if (0 === productIds.length) {
            alert($.mage.__('Please select at least one product.'));
            return;
        }
        const balReturnLink = $('#balReturnUrl').val();
        const lpcOrderId = $('#lpcOrderId').val();
        window.location.href = balReturnLink + '?orderId=' + lpcOrderId + '&productIds=' + JSON.stringify(productIds);
    });
});
