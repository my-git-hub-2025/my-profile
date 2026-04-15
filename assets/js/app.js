$(function () {
    $('#downloadPdfBtn').on('click', function () {
        window.print();
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get('print') === '1') {
        // Small delay allows content and styles to finish rendering before print.
        setTimeout(function () {
            window.print();
        }, 400);
    }
});
