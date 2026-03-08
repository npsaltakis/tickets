document.addEventListener('DOMContentLoaded', function () {
    var table = document.getElementById('report-table');

    if (!table || typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
        return;
    }

    var $table = window.jQuery(table);

    $table.DataTable({
        dom: 'Bfrtip',
        pageLength: 25,
        order: [[2, 'asc']],
        buttons: [
            {
                extend: 'excelHtml5',
                text: table.dataset.excelLabel || 'Excel',
                title: null,
                filename: table.dataset.filename || 'report',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdfHtml5',
                text: table.dataset.pdfLabel || 'PDF',
                title: null,
                filename: table.dataset.filename || 'report',
                exportOptions: {
                    columns: ':visible'
                },
                orientation: 'landscape',
                pageSize: 'A4'
            }
        ],
        language: {
            search: table.dataset.searchLabel || 'Search:',
            emptyTable: table.dataset.emptyLabel || 'No data available in table',
            info: table.dataset.infoLabel || 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: table.dataset.infoEmptyLabel || 'Showing 0 to 0 of 0 entries',
            zeroRecords: table.dataset.zeroRecordsLabel || 'No matching records found',
            lengthMenu: table.dataset.lengthMenuLabel || 'Show _MENU_ entries'
        }
    });
});
