<?php
$assetVersion = static function (string $relativePath): string {
    $fullPath = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    return is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
};
?><?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

<section class="wrapper report-page" data-report-tabs data-default-tab="<?= $selectedEvent !== null ? 'codes' : 'summary' ?>">
    <div class="events-header report-header">
        <div>
            <span class="eyebrow"><?= esc(lang('App.navReport')) ?></span>
            <h1><?= esc(lang('App.reportTitle')) ?></h1>
            <p><?= esc(lang('App.reportSubtitle')) ?></p>
        </div>
    </div>

    <div class="report-tabs" role="tablist" aria-label="<?= esc(lang('App.reportTitle')) ?>">
        <button type="button" class="report-tab-btn is-active" data-report-tab="summary" role="tab" aria-selected="true">
            <?= esc(lang('App.reportSummaryTab')) ?>
        </button>
        <button type="button" class="report-tab-btn" data-report-tab="codes" role="tab" aria-selected="false">
            <?= esc(lang('App.reportCodesTab')) ?>
        </button>
    </div>

    <div class="report-tab-panel is-active" data-report-panel="summary" role="tabpanel">
        <div class="card report-card">
            <?php if (empty($reportRows)): ?>
                <p><?= esc(lang('App.reportEmpty')) ?></p>
            <?php else: ?>
                <div class="report-table-wrap">
                    <table
                        id="report-table"
                        class="display report-table js-report-table"
                        data-search-label="<?= esc(lang('App.reportSearch')) ?>"
                        data-empty-label="<?= esc(lang('App.reportEmptyTable')) ?>"
                        data-info-label="<?= esc(lang('App.reportInfo')) ?>"
                        data-info-empty-label="<?= esc(lang('App.reportInfoEmpty')) ?>"
                        data-zero-records-label="<?= esc(lang('App.reportZeroRecords')) ?>"
                        data-length-menu-label="<?= esc(lang('App.reportLengthMenu')) ?>"
                        data-excel-label="<?= esc(lang('App.reportExportExcel')) ?>"
                        data-pdf-label="<?= esc(lang('App.reportExportPdf')) ?>"
                        data-filename="event-ticket-report"
                        data-order-column="2"
                        data-order-direction="asc"
                    >
                        <thead>
                            <tr>
                                <th><?= esc(lang('App.reportEvent')) ?></th>
                                <th><?= esc(lang('App.status')) ?></th>
                                <th><?= esc(lang('App.startDate')) ?></th>
                                <th><?= esc(lang('App.endDate')) ?></th>
                                <th><?= esc(lang('App.location')) ?></th>
                                <th><?= esc(lang('App.reportCapacity')) ?></th>
                                <th><?= esc(lang('App.reportFreeTickets')) ?></th>
                                <th><?= esc(lang('App.reportPaidTickets')) ?></th>
                                <th><?= esc(lang('App.reportCheckedInTickets')) ?></th>
                                <th><?= esc(lang('App.reportNotCheckedInTickets')) ?></th>
                                <th><?= esc(lang('App.reportRemainingSeats')) ?></th>
                                <th><?= esc(lang('App.reportDonationTotal')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportRows as $row): ?>
                                <?php $status = strtolower((string) ($row['status'] ?? 'inactive')); ?>
                                <tr>
                                    <td>
                                        <a href="<?= base_url('events/' . $row['slug']) ?>" class="report-event-link">
                                            <?= esc($row['title']) ?>
                                        </a>
                                    </td>
                                    <td><?= esc(lang('App.eventStatus' . ucfirst($status))) ?></td>
                                    <td data-order="<?= esc(strtotime((string) ($row['start_date'] ?? '')) ?: 0) ?>"><?= esc(! empty($row['start_date']) ? date('d/m/Y H:i', strtotime((string) $row['start_date'])) : '-') ?></td>
                                    <td data-order="<?= esc(strtotime((string) ($row['end_date'] ?? '')) ?: 0) ?>"><?= esc(! empty($row['end_date']) ? date('d/m/Y H:i', strtotime((string) $row['end_date'])) : '-') ?></td>
                                    <td><?= esc($row['location'] ?: '-') ?></td>
                                    <td><?= esc((string) ($row['capacity'] ?? 0)) ?></td>
                                    <td><?= esc((string) ($row['free_tickets'] ?? 0)) ?></td>
                                    <td><?= esc((string) ($row['paid_tickets'] ?? 0)) ?></td>
                                    <td><?= esc((string) ($row['checked_in_tickets'] ?? 0)) ?></td>
                                    <td><?= esc((string) ($row['not_checked_in_tickets'] ?? 0)) ?></td>
                                    <td><?= esc((string) ($row['remaining_seats'] ?? 0)) ?></td>
                                    <td>EUR <?= esc(number_format((float) ($row['donation_total'] ?? 0), 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="report-tab-panel" data-report-panel="codes" role="tabpanel">
        <div class="card report-card report-detail-card">
            <div class="report-detail-header">
                <div>
                    <h2 class="report-detail-title"><?= esc(lang('App.reportTicketsTitle')) ?></h2>
                    <p class="subtitle"><?= esc(lang('App.reportTicketsSubtitle')) ?></p>
                </div>
                <form action="<?= base_url('report') ?>" method="get" class="report-filter-form">
                    <label for="report-event-trigger" class="auth-label"><?= esc(lang('App.reportSelectEvent')) ?></label>
                    <input type="hidden" name="event_id" id="event_id" value="<?= esc((string) ($selectedEventId ?? 0)) ?>">
                    <div class="report-combobox" data-report-combobox>
                        <button type="button" id="report-event-trigger" class="auth-input report-combobox-trigger" aria-expanded="false">
                            <?= esc($selectedEvent['title'] ?? lang('App.reportSelectEventPlaceholder')) ?>
                        </button>
                        <div class="report-combobox-panel" hidden>
                            <input
                                type="search"
                                class="auth-input report-combobox-search"
                                placeholder="<?= esc(lang('App.reportEventSearchPlaceholder')) ?>"
                                data-no-results-label="<?= esc(lang('App.reportEventSearchNoResults')) ?>"
                            >
                            <div class="report-combobox-options">
                                <button type="button" class="report-combobox-option<?= (int) ($selectedEventId ?? 0) === 0 ? ' is-selected' : '' ?>" data-value="0">
                                    <?= esc(lang('App.reportSelectEventPlaceholder')) ?>
                                </button>
                                <?php foreach ($reportRows as $row): ?>
                                    <button
                                        type="button"
                                        class="report-combobox-option<?= (int) ($selectedEventId ?? 0) === (int) $row['id'] ? ' is-selected' : '' ?>"
                                        data-value="<?= esc((string) $row['id']) ?>"
                                    >
                                        <?= esc($row['title']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="report-filter-controls">
                        <button type="submit" class="book-btn"><?= esc(lang('App.reportLoadTickets')) ?></button>
                    </div>
                </form>
            </div>

            <?php if ($selectedEvent !== null): ?>
                <div class="report-selected-event-meta">
                    <strong><?= esc($selectedEvent['title']) ?></strong>
                    <span><?= esc(lang('App.reportIssuedTickets')) ?>: <?= esc((string) ($selectedEvent['issued_tickets'] ?? 0)) ?></span>
                </div>

                <?php if (empty($ticketRows)): ?>
                    <p><?= esc(lang('App.reportTicketsEmpty')) ?></p>
                <?php else: ?>
                    <div class="report-table-wrap">
                        <table
                            id="report-ticket-table"
                            class="display report-table js-report-table"
                            data-search-label="<?= esc(lang('App.reportSearch')) ?>"
                            data-empty-label="<?= esc(lang('App.reportEmptyTable')) ?>"
                            data-info-label="<?= esc(lang('App.reportInfo')) ?>"
                            data-info-empty-label="<?= esc(lang('App.reportInfoEmpty')) ?>"
                            data-zero-records-label="<?= esc(lang('App.reportZeroRecords')) ?>"
                            data-length-menu-label="<?= esc(lang('App.reportLengthMenu')) ?>"
                            data-excel-label="<?= esc(lang('App.reportExportExcel')) ?>"
                            data-pdf-label="<?= esc(lang('App.reportExportPdf')) ?>"
                            data-filename="<?= esc('event-tickets-' . ($selectedEvent['slug'] ?? 'report')) ?>"
                            data-order-column="5"
                            data-order-direction="desc"
                        >
                            <thead>
                                <tr>
                                    <th><?= esc(lang('App.reportTicketCode')) ?></th>
                                    <th><?= esc(lang('App.reportCustomer')) ?></th>
                                    <th><?= esc(lang('App.reportCustomerEmail')) ?></th>
                                    <th><?= esc(lang('App.reportPaymentStatus')) ?></th>
                                    <th><?= esc(lang('App.reportGatewayStatus')) ?></th>
                                    <th><?= esc(lang('App.reportPayPalTransaction')) ?></th>
                                    <th><?= esc(lang('App.reportDonationAmount')) ?></th>
                                    <th><?= esc(lang('App.reportBookedAt')) ?></th>
                                    <th><?= esc(lang('App.reportCheckInStatus')) ?></th>
                                    <th><?= esc(lang('App.checkInCheckedInAt')) ?></th>
                                    <th><?= esc(lang('App.reportCheckedInBy')) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ticketRows as $ticketRow): ?>
                                    <?php $paymentStatus = (string) ($ticketRow['payment_status'] ?? 'free'); ?>
                                    <tr>
                                        <td><code><?= esc($ticketRow['ticket_code'] ?? '') ?></code></td>
                                        <td><?= esc(($ticketRow['customer_name'] ?? '') !== '' ? $ticketRow['customer_name'] : '-') ?></td>
                                        <td><?= esc($ticketRow['email'] ?? '-') ?></td>
                                        <td><?= esc(lang('App.paymentStatus' . ucfirst($paymentStatus))) ?></td>
                                        <td><?= esc((string) ($ticketRow['gateway_payment_status'] ?? '-')) ?></td>
                                        <td><code><?= esc((string) ($ticketRow['paypal_transaction_id'] ?? '-')) ?></code></td>
                                        <td>EUR <?= esc(number_format((float) ($ticketRow['donation_amount'] ?? 0), 2)) ?></td>
                                        <td data-order="<?= esc(strtotime((string) ($ticketRow['booked_at'] ?? '')) ?: 0) ?>"><?= esc(! empty($ticketRow['booked_at']) ? date('d/m/Y H:i', strtotime((string) $ticketRow['booked_at'])) : '-') ?></td>
                                        <td><?= esc((string) ($ticketRow['checked_in_label'] ?? '-')) ?></td>
                                        <td data-order="<?= esc(strtotime((string) ($ticketRow['checked_in_at'] ?? '')) ?: 0) ?>"><?= esc((string) ($ticketRow['checked_in_at_formatted'] ?? '-')) ?></td>
                                        <td><?= esc((string) ($ticketRow['checked_in_by_name'] ?? '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p><?= esc(lang('App.reportSelectEventHelp')) ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="<?= base_url('assets/js/report.js') ?>?v=<?= esc($assetVersion('assets/js/report.js')) ?>"></script>
<?= $this->endSection() ?>

