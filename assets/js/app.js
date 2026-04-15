$(function () {
    $('#downloadPdfBtn').on('click', function () {
        window.print();
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get('print') === '1') {
        setTimeout(function () {
            window.print();
        }, 400);
    }
});
