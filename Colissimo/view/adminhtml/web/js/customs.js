require([
    'jquery',
    'mage/backend/notification'
], function ($, notification) {
    'use strict';
    jQuery(document).ready(function () {
        if (0 === $('.lpc__admin__order_banner__send_documents').length) {
            $('#sales_order_view_tabs_customs_tab').closest('li.admin__page-nav-item').hide();
        }

        // Init Add more buttons
        let $addMoreButtons = $('.lpc__admin__order_banner__send_documents__more');
        $addMoreButtons.off('click').on('click', function () {
            let template = document.querySelector('#lpc__admin__order_banner__send_documents__template').innerHTML;
            let parcelNumber = $(this).attr('data-lpc-parcelnumber');

            $(this)
                .closest('.lpc__admin__order_banner__send_documents__container')
                .find('.lpc__admin__order_banner__send_documents__listing')
                .append(template.replace('__PARCELNUMBER__', parcelNumber));

            // Init the document type field
            $('.lpc__admin__order_banner__document__type').off('change').on('change', function () {
                let selectedType = $(this).val();
                if (0 === selectedType.length) {
                    selectedType = '__TYPE__';
                }
                let $fileInput = $(this)
                    .closest('tr')
                    .find('.lpc__admin__order_banner__document__file');
                $fileInput.attr('name', $fileInput.attr('name').replace(/\[[A-Z0-9_]+\]\[\]/, '[' + selectedType + '][]'));
                $fileInput.prop('disabled', 0 === selectedType.length || '__TYPE__' === selectedType);
            });
        });

        // Add a default document row
        $addMoreButtons.each(function () {
            let $rows = $(this)
                .closest('.lpc__admin__order_banner__send_documents__container')
                .find('.lpc__admin__order_banner__send_documents__listing tr').not('.lpc__customs__sent__document');
            if (0 === $rows.length) {
                $(this).click();
            }
        });

        $('.lpc__admin__order_banner__send_documents__listing__send_button').off('click').on('click', function (event) {
            event.preventDefault();

            var fd = new FormData();
            var files = 0;
            $(this).closest('.lpc__admin__order_banner__send_documents__container').find('input[type="file"]').each(function () {
                if (0 < $(this)[0].files.length) {
                    fd.append($(this).attr('name'), $(this)[0].files[0]);
                    files++;
                }
            });

            if (0 === files) return;

            var $form = $(this).closest('form');
            var $spinner = $form.siblings('img.processing');
            fd.append('form_key', $form.attr('data-lpc-formkey'));
            fd.append('shipment_id', $form.attr('data-lpc-shipmentid'));

            $form.css('opacity', '0.4');
            $spinner.css('display', 'block');
            $.ajax({
                url: $form.attr('data-lpc-ajaxurl'),
                type: 'POST',
                data: fd,
                cache: false,
                contentType: false,
                processData: false,
                dataType: 'json',
                complete: function (response) {
                    response = response.responseJSON;

                    $form.css('opacity', '1');
                    $spinner.css('display', 'none');

                    if ('error' === response.type) {
                        var $body = $('body');
                        $body.notification('clear');
                        for (var i in response.data.errors) {
                            if (!response.data.errors.hasOwnProperty(i)) continue;

                            $body.notification('add', {
                                error: true,
                                message: response.data.errors[i],

                                insertMethod: function (message) {
                                    var $wrapper = $('<div></div>').html(message);

                                    $('.page-main-actions').after($wrapper);
                                }
                            });
                        }
                    } else {
                        location.reload();
                    }
                }
            });
        });
    });
});
