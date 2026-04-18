<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main
    class="wrapper"
    data-check-in-scanner
    data-start-label="<?= esc(lang('App.checkInCameraStart'), 'attr') ?>"
    data-stop-label="<?= esc(lang('App.checkInCameraStop'), 'attr') ?>"
    data-camera-error="<?= esc(lang('App.checkInCameraError'), 'attr') ?>"
    data-camera-ready="<?= esc(lang('App.checkInCameraReady'), 'attr') ?>"
    data-camera-stopped="<?= esc(lang('App.checkInCameraStopped'), 'attr') ?>"
>
    <section class="auth-card check-in-card">
        <h1 class="auth-title"><?= esc(lang('App.checkInTitle')) ?></h1>
        <p class="subtitle"><?= esc(lang('App.checkInSubtitle')) ?></p>

        <div class="check-in-scanner-panel">
            <div class="check-in-scanner-actions">
                <button type="button" class="book-btn" id="check-in-camera-start"><?= esc(lang('App.checkInCameraStart')) ?></button>
                <button type="button" class="ticket-export-btn ticket-export-btn--secondary" id="check-in-camera-stop" disabled><?= esc(lang('App.checkInCameraStop')) ?></button>
            </div>
            <p class="subtitle check-in-camera-hint"><?= esc(lang('App.checkInCameraHint')) ?></p>
            <p class="check-in-camera-status" id="check-in-camera-status"><?= esc(lang('App.checkInCameraIdle')) ?></p>
            <div id="check-in-camera-reader" class="check-in-camera-reader" hidden></div>
        </div>

        <form method="post" action="<?= base_url('check-in') ?>" class="auth-form" id="check-in-form">
            <?= csrf_field() ?>

            <label for="ticket_code" class="auth-label"><?= esc(lang('App.checkInCodeLabel')) ?></label>
            <input
                id="ticket_code"
                name="ticket_code"
                type="text"
                class="auth-input check-in-input"
                value="<?= esc((string) ($enteredCode ?? '')) ?>"
                placeholder="<?= esc(lang('App.checkInCodePlaceholder')) ?>"
                required
                autofocus
                autocomplete="off"
                spellcheck="false"
            >

            <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.checkInButton')) ?></button>
        </form>

        <?php if (! empty($result) && is_array($result)): ?>
            <?php
            $resultType = (string) ($result['type'] ?? 'info');
            $resultClass = $resultType === 'success' ? 'auth-info' : ($resultType === 'warning' ? 'check-in-warning' : 'auth-error');
            $details = is_array($result['details'] ?? null) ? $result['details'] : [];
            ?>
            <div class="check-in-result">
                <p class="<?= esc($resultClass) ?>"><?= esc((string) ($result['message'] ?? '')) ?></p>

                <?php if ($details !== []): ?>
                    <div class="card check-in-result-card">
                        <div class="check-in-result-grid">
                            <div>
                                <span class="check-in-detail-label"><?= esc(lang('App.reportTicketCode')) ?></span>
                                <strong><?= esc((string) ($details['ticket_code'] ?? '-')) ?></strong>
                            </div>
                            <div>
                                <span class="check-in-detail-label"><?= esc(lang('App.reportEvent')) ?></span>
                                <strong><?= esc((string) ($details['event_title'] ?? '-')) ?></strong>
                            </div>
                            <div>
                                <span class="check-in-detail-label"><?= esc(lang('App.startDate')) ?></span>
                                <strong><?= esc((string) ($details['event_start_date'] ?? '-')) ?></strong>
                            </div>
                            <div>
                                <span class="check-in-detail-label"><?= esc(lang('App.location')) ?></span>
                                <strong><?= esc((string) ($details['event_location'] ?? '-')) ?></strong>
                            </div>
                            <div>
                                <span class="check-in-detail-label"><?= esc(lang('App.reportCustomer')) ?></span>
                                <strong><?= esc((string) ($details['customer_name'] ?? '-')) ?></strong>
                            </div>
                            <div>
                                <span class="check-in-detail-label"><?= esc(lang('App.reportCustomerEmail')) ?></span>
                                <strong><?= esc((string) ($details['customer_email'] ?? '-')) ?></strong>
                            </div>
                            <div>
                                <span class="check-in-detail-label"><?= esc(lang('App.reportPaymentStatus')) ?></span>
                                <strong><?= esc((string) ($details['payment_status_label'] ?? '-')) ?></strong>
                            </div>
                            <div>
                                <span class="check-in-detail-label"><?= esc(lang('App.reportDonationAmount')) ?></span>
                                <strong><?= esc((string) ($details['donation_amount'] ?? '-')) ?></strong>
                            </div>
                            <div>
                                <span class="check-in-detail-label"><?= esc(lang('App.reportBookedAt')) ?></span>
                                <strong><?= esc((string) ($details['booked_at'] ?? '-')) ?></strong>
                            </div>
                            <?php if (! empty($details['checked_in_at'])): ?>
                                <div>
                                    <span class="check-in-detail-label"><?= esc(lang('App.checkInCheckedInAt')) ?></span>
                                    <strong><?= esc((string) $details['checked_in_at']) ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="<?= base_url('assets/js/check-in.js') ?>?v=<?= esc((string) (is_file(FCPATH . 'assets/js/check-in.js') ? filemtime(FCPATH . 'assets/js/check-in.js') : time())) ?>"></script>
<?= $this->endSection() ?>
