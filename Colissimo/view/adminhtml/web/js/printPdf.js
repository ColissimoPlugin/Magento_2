document.addEventListener('DOMContentLoaded', function () {
    var ePdf = document.getElementById('lpc-label-pdf');

    if (ePdf && ePdf.tagName === 'IFRAME') {
        ePdf.contentWindow.focus();
        ePdf.contentWindow.print();
    }
});