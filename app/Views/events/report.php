<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

<section class="wrapper report-page">
    <div class="events-header report-header">
        <div>
            <span class="eyebrow"><?= esc(lang('App.navReport')) ?></span>
            <h1><?= esc(lang('App.reportTitle')) ?></h1>
            <p><?= esc(lang('App.reportSubtitle')) ?></p>
        </div>
    </div>

    <div class="card report-card">
        <?php if (empty($rows)): ?>
            <p><?= esc(lang('App.reportEmpty')) ?></p>
        <?php else: ?>
            <div class="report-table-wrap">
                <table
                    id="report-table"
                    class="display report-table"
                    data-search-label="<?= esc(lang('App.reportSearch')) ?>"
                    data-empty-label="<?= esc(lang('App.reportEmptyTable')) ?>"
                    data-info-label="<?= esc(lang('App.reportInfo')) ?>"
                    data-info-empty-label="<?= esc(lang('App.reportInfoEmpty')) ?>"
                    data-zero-records-label="<?= esc(lang('App.reportZeroRecords')) ?>"
                    data-length-menu-label="<?= esc(lang('App.reportLengthMenu')) ?>"
                    data-excel-label="<?= esc(lang('App.reportExportExcel')) ?>"
                    data-pdf-label="<?= esc(lang('App.reportExportPdf')) ?>"
                    data-filename="event-ticket-report"
                >
                    <thead>
                        <tr>
                            <th><?= esc(lang('App.reportEvent')) ?></th>
                            <th><?= esc(lang('App.status')) ?></th>
                            <th><?= esc(lang('App.startDate')) ?></th>
                            <th><?= esc(lang('App.endDate')) ?></th>
                            <th><?= esc(lang('App.location')) ?></th>
                            <th><?= esc(lang('App.reportCapacity')) ?></th>                            <th><?= esc(lang('App.reportFreeTickets')) ?></th>
                            <th><?= esc(lang('App.reportPaidTickets')) ?></th>
                            <th><?= esc(lang('App.reportRemainingSeats')) ?></th>
                            <th><?= esc(lang('App.reportDonationTotal')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
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
                                <td><?= esc((string) ($row['remaining_seats'] ?? 0)) ?></td>
                                <td>EUR <?= esc(number_format((float) ($row['donation_total'] ?? 0), 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="<?= base_url('assets/js/report.js') ?>"></script>
<?= $this->endSection() ?>


