<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <section class="auth-card check-in-card">
        <h1 class="auth-title"><?= esc(lang('App.checkInTitle')) ?></h1>
        <p class="subtitle"><?= esc(lang('App.checkInSubtitle')) ?></p>

        <form method="post" action="<?= base_url('check-in') ?>" class="auth-form">
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
<?= $this->endSection() ?>
