document.addEventListener('DOMContentLoaded', function () {
    var root = document.querySelector('[data-my-events-pdf]');

    if (!root || typeof window.pdfMake === 'undefined') {
        return;
    }

    var labels = {
        ticket: root.dataset.ticketLabel || 'Ticket Code',
        event: root.dataset.eventLabel || 'Event',
        start: root.dataset.startLabel || 'Start',
        end: root.dataset.endLabel || 'End',
        location: root.dataset.locationLabel || 'Location',
        address: root.dataset.addressLabel || 'Address',
        bookedAt: root.dataset.bookedAtLabel || 'Booked at',
        payment: root.dataset.paymentLabel || 'Payment',
        donation: root.dataset.donationLabel || 'Donation',
        title: root.dataset.exportTitle || 'Event Ticket',
        subtitle: root.dataset.exportSubtitle || '',
        filenamePrefix: root.dataset.exportFilenamePrefix || 'ticket',
    };

    function safeSlug(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'ticket';
    }

    function parseEvent(button) {
        try {
            return JSON.parse(button.dataset.event || '{}');
        } catch (error) {
            return {};
        }
    }

    function ticketDocument(eventData, ticketCode) {
        var details = [];

        [
            [labels.event, eventData.title],
            [labels.start, eventData.start_date],
            [labels.end, eventData.end_date],
            [labels.location, eventData.location],
            [labels.address, eventData.address],
            [labels.bookedAt, eventData.booked_at],
            [labels.payment, eventData.payment_status],
            [labels.donation, eventData.donation_total],
        ].forEach(function (pair) {
            if (!pair[1]) {
                return;
            }

            details.push([
                { text: pair[0], style: 'detailLabel' },
                { text: String(pair[1]), style: 'detailValue' }
            ]);
        });

        return {
            pageSize: 'A4',
            pageMargins: [36, 42, 36, 42],
            content: [
                {
                    stack: [
                        { text: labels.title, style: 'heroTitle' },
                        labels.subtitle ? { text: labels.subtitle, style: 'heroSubtitle' } : null,
                    ].filter(Boolean),
                    margin: [0, 0, 0, 22]
                },
                {
                    columns: [
                        {
                            width: '*',
                            stack: [
                                {
                                    table: {
                                        widths: ['auto', '*'],
                                        body: details,
                                    },
                                    layout: 'noBorders'
                                },
                                {
                                    margin: [0, 22, 0, 0],
                                    stack: [
                                        { text: labels.ticket, style: 'ticketLabel' },
                                        { text: String(ticketCode), style: 'ticketCode' }
                                    ]
                                }
                            ]
                        },
                        {
                            width: 190,
                            stack: [
                                {
                                    qr: String(ticketCode),
                                    fit: 160,
                                    foreground: '#0f172a',
                                    margin: [0, 0, 0, 12]
                                },
                                {
                                    text: String(ticketCode),
                                    style: 'qrCaption',
                                    alignment: 'center'
                                }
                            ]
                        }
                    ],
                    columnGap: 24
                }
            ],
            styles: {
                heroTitle: {
                    fontSize: 24,
                    bold: true,
                    color: '#0f172a'
                },
                heroSubtitle: {
                    margin: [0, 8, 0, 0],
                    fontSize: 11,
                    color: '#475569'
                },
                detailLabel: {
                    fontSize: 10,
                    bold: true,
                    color: '#64748b',
                    margin: [0, 0, 14, 10]
                },
                detailValue: {
                    fontSize: 12,
                    color: '#0f172a',
                    margin: [0, 0, 0, 10]
                },
                ticketLabel: {
                    fontSize: 10,
                    bold: true,
                    color: '#1d4ed8',
                    margin: [0, 0, 0, 6]
                },
                ticketCode: {
                    fontSize: 18,
                    bold: true,
                    color: '#0f172a'
                },
                qrCaption: {
                    fontSize: 10,
                    color: '#475569'
                }
            },
            defaultStyle: {
                fontSize: 11
            }
        };
    }

    root.querySelectorAll('[data-export-ticket-pdf]').forEach(function (button) {
        button.addEventListener('click', function () {
            var eventData = parseEvent(button);
            var ticketCode = button.dataset.ticketCode || '';

            if (!ticketCode) {
                return;
            }

            var filename = safeSlug(labels.filenamePrefix + '-' + ticketCode) + '.pdf';
            window.pdfMake.createPdf(ticketDocument(eventData, ticketCode)).download(filename);
        });
    });

    root.querySelectorAll('[data-export-all-tickets]').forEach(function (button) {
        button.addEventListener('click', function () {
            var eventData = parseEvent(button);
            var tickets = [];

            try {
                tickets = JSON.parse(button.dataset.tickets || '[]');
            } catch (error) {
                tickets = [];
            }

            tickets.forEach(function (ticketCode) {
                if (!ticketCode) {
                    return;
                }

                var filename = safeSlug(labels.filenamePrefix + '-' + ticketCode) + '.pdf';
                window.pdfMake.createPdf(ticketDocument(eventData, ticketCode)).download(filename);
            });
        });
    });
});
