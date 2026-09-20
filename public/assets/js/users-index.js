document.querySelectorAll('[data-confirm-action]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const message = form.getAttribute('data-confirm-message') || 'Are you sure?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-bulk-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const checked = document.querySelectorAll('.users-select-row:checked').length;

        if (checked === 0) {
            event.preventDefault();
            window.alert(form.dataset.noneMessage || 'Select at least one user.');
            return;
        }

        if (!window.confirm((form.dataset.confirmMessage || 'Apply to {n} users?').replace('{n}', String(checked)))) {
            event.preventDefault();
        }
    });
});

document.addEventListener('change', (event) => {
    if (event.target && event.target.classList.contains('users-select-all')) {
        document.querySelectorAll('.users-select-row:not(:disabled)').forEach((box) => {
            box.checked = event.target.checked;
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
        return;
    }

    var tables = document.querySelectorAll('.js-users-table');
    if (!tables.length) {
        return;
    }

    tables.forEach(function (table) {
        var orderColumn = parseInt(table.dataset.orderColumn || '0', 10);
        var orderDirection = table.dataset.orderDirection || 'asc';
        var lastColumn = table.querySelectorAll('thead th').length - 1;

        window.jQuery(table).DataTable({
            dom: 'lfrtip',
            paging: true,
            lengthChange: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[orderColumn, orderDirection]],
            columnDefs: [
                { orderable: false, searchable: false, targets: table.dataset.selectable ? [0, lastColumn] : lastColumn },
            ],
            language: {
                search: table.dataset.searchLabel || 'Search:',
                emptyTable: table.dataset.emptyLabel || 'No data available in table',
                info: table.dataset.infoLabel || 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: table.dataset.infoEmptyLabel || 'Showing 0 to 0 of 0 entries',
                zeroRecords: table.dataset.zeroRecordsLabel || 'No matching records found',
                lengthMenu: table.dataset.lengthMenuLabel || 'Show _MENU_ entries',
            },
        });
    });
});
