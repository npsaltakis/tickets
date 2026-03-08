document.addEventListener('DOMContentLoaded', function () {
    var tabsRoot = document.querySelector('[data-report-tabs]');

    if (tabsRoot) {
        var defaultTab = tabsRoot.dataset.defaultTab || 'summary';
        var tabButtons = tabsRoot.querySelectorAll('[data-report-tab]');
        var tabPanels = tabsRoot.querySelectorAll('[data-report-panel]');

        var setActiveTab = function (tabName) {
            tabButtons.forEach(function (button) {
                var isActive = button.dataset.reportTab === tabName;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            tabPanels.forEach(function (panel) {
                panel.classList.toggle('is-active', panel.dataset.reportPanel === tabName);
            });
        };

        setActiveTab(defaultTab);

        tabButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                setActiveTab(button.dataset.reportTab || 'summary');
            });
        });
    }

    var combobox = document.querySelector('[data-report-combobox]');
    if (combobox) {
        var hiddenInput = document.getElementById('event_id');
        var trigger = combobox.querySelector('.report-combobox-trigger');
        var panel = combobox.querySelector('.report-combobox-panel');
        var searchInput = combobox.querySelector('.report-combobox-search');
        var options = Array.from(combobox.querySelectorAll('.report-combobox-option'));
        var noResultsLabel = searchInput ? (searchInput.dataset.noResultsLabel || 'No matching events') : 'No matching events';
        var noResultsNode = document.createElement('div');
        noResultsNode.className = 'report-combobox-empty';
        noResultsNode.textContent = noResultsLabel;
        noResultsNode.hidden = true;
        if (panel) {
            panel.appendChild(noResultsNode);
        }

        var closePanel = function () {
            if (!panel || !trigger) {
                return;
            }

            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        };

        var openPanel = function () {
            if (!panel || !trigger) {
                return;
            }

            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');

            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        };

        var updateSelection = function (selectedOption) {
            options.forEach(function (option) {
                option.classList.toggle('is-selected', option === selectedOption);
            });

            if (hiddenInput) {
                hiddenInput.value = selectedOption ? (selectedOption.dataset.value || '0') : '0';
            }

            if (trigger) {
                trigger.textContent = selectedOption ? selectedOption.textContent.trim() : '';
            }
        };

        var filterOptions = function (query) {
            var normalizedQuery = (query || '').trim().toLowerCase();
            var visibleCount = 0;

            options.forEach(function (option) {
                var matches = normalizedQuery === '' || option.textContent.toLowerCase().indexOf(normalizedQuery) !== -1;
                option.hidden = !matches;
                if (matches) {
                    visibleCount++;
                }
            });

            noResultsNode.hidden = visibleCount !== 0;
        };

        if (trigger) {
            trigger.addEventListener('click', function () {
                if (panel && panel.hidden === false) {
                    closePanel();
                } else {
                    openPanel();
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                filterOptions(searchInput.value);
            });
        }

        options.forEach(function (option) {
            option.addEventListener('click', function () {
                updateSelection(option);
                if (searchInput) {
                    searchInput.value = '';
                    filterOptions('');
                }
                closePanel();
            });
        });

        document.addEventListener('click', function (event) {
            if (!combobox.contains(event.target)) {
                closePanel();
            }
        });
    }

    if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
        return;
    }

    var tables = document.querySelectorAll('.js-report-table');
    if (!tables.length) {
        return;
    }

    tables.forEach(function (table) {
        var orderColumn = parseInt(table.dataset.orderColumn || '0', 10);
        var orderDirection = table.dataset.orderDirection || 'asc';

        window.jQuery(table).DataTable({
            dom: 'Blfrtip',
            paging: true,
            lengthChange: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[orderColumn, orderDirection]],
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
});

