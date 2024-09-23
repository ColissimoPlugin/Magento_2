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
        window.location.href = returnLabelDownloadLink + '?orderId=' + lpcOrderId + '&productIds=' + JSON.stringify(productIds);
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
