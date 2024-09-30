require([
    'jquery'
], function ($) {
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
