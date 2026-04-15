$(function () {
    const PRINT_RENDER_DELAY = 400;

    $('#downloadPdfBtn').on('click', function () {
        window.print();
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get('print') === '1') {
        // Delay allows content and styles to finish rendering before print.
        setTimeout(function () {
            window.print();
        }, PRINT_RENDER_DELAY);
    }
});
