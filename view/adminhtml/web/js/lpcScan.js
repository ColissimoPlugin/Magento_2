define([
    'jquery',
    'mage/translate'
], function ($, $t) {

    return function (config, element) {
        var $element = $(element);
        var $scanInfo = $('#lpc_scan_info');
        var $scanBarcodes = $('#lpc_scan_barcodes');

        $element.focus();

        // Check if code is a valid domicile method code
        var testCodeDomicile = function (code) {
            var pattern1Num = new RegExp('[0-9]');
            var pattern1Alpha = new RegExp('[a-zA-Z]');
            if (code.substring(0, 1) == '%' && code.substr(8, 2) == '11' && pattern1Num.test(code.substr(10, 1)) == true && pattern1Alpha.test(code.substr(11,
                1
            )) == true && code.substr(25, 3) == '250') {
                return true;
            } else {
                return false;
            }
        };

        // Calculate the code from the scan for domicile method
        var calculateCode = function (code) {
            var tmpCode = code.substr(12, 10);
            var sum1 = parseInt(tmpCode[1]) + parseInt(tmpCode[3]) + parseInt(tmpCode[5]) + parseInt(tmpCode[7]) + parseInt(tmpCode[9]);
            var sum2 = parseInt(tmpCode[0]) + parseInt(tmpCode[2]) + parseInt(tmpCode[4]) + parseInt(tmpCode[6]) + parseInt(tmpCode[8]);
            var total = 3 * sum1 + sum2;
            var res = (Math.ceil(total / 10) * 10) - total;
            return code.substr(10, 12) + res;
        };


        $element.on('change', function (e) {
            // Get new added code and check before adding to code list
            var newCode = $element.val();
            var codes = $scanBarcodes.val();

            if (newCode.length == config.codeLength && $.inArray(newCode.substr(0, 2), config.allowedPrefix) >= 0) {
                // tracking number
                $scanInfo.switchClass('lpc_wrongCode lpc_info', 'lpc_goodCode').text($t('Code added to list: ') + newCode);
                $scanBarcodes.val(codes + newCode + '\r\n');
                $element.val('');
            } else if (newCode.length > config.codeLength && $.inArray(newCode.substr(10, 2), config.allowedPrefix) >= 0 && testCodeDomicile(newCode)) {
                // tracking number enclosed in the code (Domicile)
                newCode = calculateCode(newCode);
                $scanInfo.switchClass('lpc_wrongCode lpc_info', 'lpc_goodCode').text($t('Code added to list: ') + newCode);
                $scanBarcodes.val(codes + newCode + '\r\n');
                $element.val('');
            } else if (newCode.length > config.codeLength && newCode.substring(0, 1) == '%' && $.inArray(newCode.substr(8, 2), config.allowedPrefix) >= 0) {
                // tracking number enclosed in the code (relay point second code)
                newCode = newCode.substr(8, config.codeLength);
                $scanInfo.switchClass('lpc_wrongCode lpc_info', 'lpc_goodCode').text($t('Code added to list: ') + newCode);
                $scanBarcodes.val(codes + newCode + '\r\n');
                $element.val('');
            } else {
                // Other cases should not be added
                $scanInfo.switchClass('lpc_goodCode lpc_info', 'lpc_wrongCode').text($t('Wrong data, code not added: ') + newCode);
                $element.val('');
            }
        });

        // Reset all codes
        $('#lpc_scan_reset').on('click', function () {
            $scanBarcodes.val('');
            $scanInfo.removeClass('lpc_wrongCode lpc_goodCode lpc_info').text('');
            $element.focus();
        });

        // Remove last code from the list
        $('#lpc_scan_remove_last').on('click', function () {
            var allCodes = $scanBarcodes.val();
            var removedCode = allCodes.substr(allCodes.length - 14, config.codeLength);
            $scanBarcodes.val(allCodes.substr(0, allCodes.length - 15) + '\r\n');
            $scanInfo.switchClass('lpc_wrongCode lpc_goodCode', 'lpc_info').text($t('Code removed: ') + removedCode);
            $element.focus();
        });
    };
});
