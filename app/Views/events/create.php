<?php
$assetVersion = static function (string $relativePath): string {
    $fullPath = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    return is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
};
?><?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <?php
    $isEditMode = (bool) ($isEditMode ?? false);
    $event = is_array($event ?? null) ? $event : [];
    $oldSlug = (string) old('slug', (string) ($event['slug'] ?? ''));
    $imageValue = (string) old('image', (string) ($event['image'] ?? ''));
    $startDateValue = (string) old('start_date', ! empty($event['start_date']) ? date('Y-m-d', strtotime((string) $event['start_date'])) : '');
    $startTimeValue = (string) old('start_time', ! empty($event['start_date']) ? date('H:i', strtotime((string) $event['start_date'])) : '');
    $endDateValue = (string) old('end_date', ! empty($event['end_date']) ? date('Y-m-d', strtotime((string) $event['end_date'])) : '');
    $endTimeValue = (string) old('end_time', ! empty($event['end_date']) ? date('H:i', strtotime((string) $event['end_date'])) : '');
    $selectedType = (string) old('event_type', (string) ($event['event_type'] ?? 'free'));
    $selectedFormat = (string) old('event_format', (string) ($event['event_format'] ?? 'physical'));
    $selectedStatus = (string) old('status', (string) ($event['status'] ?? 'active'));
    $bookingsEnabled = (string) old('bookings_enabled', (string) ($event['bookings_enabled'] ?? '1')) === '1';
    $formAction = $isEditMode ? base_url('events/' . $event['slug'] . '/update') : base_url('events');
    $heading = $isEditMode ? lang('App.eventEditTitle') : lang('App.eventCreateTitle');
    $subtitle = $isEditMode ? lang('App.eventEditSubtitle') : lang('App.eventCreateSubtitle');
    $submitLabel = $isEditMode ? lang('App.eventEditSubmitButton') : lang('App.eventCreateSubmitButton');
    $timeOptions = [];
    for ($hour = 0; $hour < 24; $hour++) {
        foreach ([0, 15, 30, 45] as $minute) {
            $timeOptions[] = sprintf('%02d:%02d', $hour, $minute);
        }
    }
    ?>
    <a class="back-link" href="<?= base_url('/') ?>">&larr; <?= esc(lang('App.backToEvents')) ?></a>

    <section class="event-form-card">
        <div class="event-form-header">
            <h1 class="auth-title"><?= esc($heading) ?></h1>
            <p class="subtitle"><?= esc($subtitle) ?></p>
        </div>

        <?php if (session()->getFlashdata('event_error')): ?>
            <p class="auth-error"><?= esc((string) session()->getFlashdata('event_error')) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= $formAction ?>" class="event-form-grid" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="event-field event-field-full">
                <label for="title" class="auth-label"><?= esc(lang('App.eventCreateTitleLabel')) ?></label>
                <input id="title" name="title" type="text" class="auth-input" value="<?= esc((string) old('title', (string) ($event['title'] ?? ''))) ?>" required>
            </div>

            <div class="event-field">
                <label for="slug" class="auth-label"><?= esc(lang('App.eventCreateSlugLabel')) ?></label>
                <input id="slug" name="slug" type="text" class="auth-input" value="<?= esc($oldSlug) ?>" data-initial-slug="<?= esc($oldSlug, 'attr') ?>" readonly>
                <p class="field-hint"><?= esc(lang('App.eventCreateSlugHint')) ?></p>
            </div>

            <div class="event-field">
                <label for="event_format" class="auth-label"><?= esc(lang('App.eventCreateFormatLabel')) ?></label>
                <select id="event_format" name="event_format" class="auth-input" required>
                    <option value="physical" <?= $selectedFormat === 'physical' ? 'selected' : '' ?>><?= esc(lang('App.eventFormatPhysical')) ?></option>
                    <option value="online" <?= $selectedFormat === 'online' ? 'selected' : '' ?>><?= esc(lang('App.eventFormatOnline')) ?></option>
                    <option value="hybrid" <?= $selectedFormat === 'hybrid' ? 'selected' : '' ?>><?= esc(lang('App.eventFormatHybrid')) ?></option>
                </select>
                <p class="field-hint"><?= esc(lang('App.eventCreateFormatHint')) ?></p>
            </div>

            <div class="event-field">
                <label for="location" class="auth-label"><?= esc(lang('App.eventCreateLocationLabel')) ?></label>
                <input id="location" name="location" type="text" class="auth-input" value="<?= esc((string) old('location', (string) ($event['location'] ?? ''))) ?>" required>
                <p class="field-hint"><?= esc(lang('App.eventCreateLocationHint')) ?></p>
            </div>

            <div class="event-field event-field-full" id="address-field-wrapper">
                <label for="address" class="auth-label"><?= esc(lang('App.eventCreateAddressLabel')) ?></label>
                <input id="address" name="address" type="text" class="auth-input" value="<?= esc((string) old('address', (string) ($event['address'] ?? ''))) ?>" <?= in_array($selectedFormat, ['physical', 'hybrid'], true) ? 'required' : '' ?>>
                <p class="field-hint"><?= esc(lang('App.eventCreateAddressHint')) ?></p>
            </div>

            <div class="event-field event-field-full">
                <label for="online_url" class="auth-label"><?= esc(lang('App.eventCreateOnlineUrlLabel')) ?></label>
                <input id="online_url" name="online_url" type="url" class="auth-input" value="<?= esc((string) old('online_url', (string) ($event['online_url'] ?? ''))) ?>" placeholder="https://example.com/live-room">
                <p class="field-hint"><?= esc(lang('App.eventCreateOnlineUrlHint')) ?></p>
            </div>

            <div class="event-field event-field-full">
                <label for="online_access_notes" class="auth-label"><?= esc(lang('App.eventCreateOnlineAccessNotesLabel')) ?></label>
                <textarea id="online_access_notes" name="online_access_notes" class="auth-input event-textarea" rows="4"><?= esc((string) old('online_access_notes', (string) ($event['online_access_notes'] ?? ''))) ?></textarea>
                <p class="field-hint"><?= esc(lang('App.eventCreateOnlineAccessNotesHint')) ?></p>
            </div>

            <div class="event-field">
                <label for="info_phone" class="auth-label"><?= esc(lang('App.eventCreateInfoPhoneLabel')) ?></label>
                <input id="info_phone" name="info_phone" type="text" class="auth-input" value="<?= esc((string) old('info_phone', (string) ($event['info_phone'] ?? ''))) ?>">
            </div>

            <div class="event-field">
                <label for="info_url" class="auth-label"><?= esc(lang('App.eventCreateInfoUrlLabel')) ?></label>
                <input id="info_url" name="info_url" type="url" class="auth-input" value="<?= esc((string) old('info_url', (string) ($event['info_url'] ?? ''))) ?>" placeholder="https://example.com/details">
                <p class="field-hint"><?= esc(lang('App.eventCreateInfoUrlHint')) ?></p>
            </div>

            <div class="event-field event-field-full">
                <label for="image" class="auth-label"><?= esc(lang('App.eventCreateImageLabel')) ?></label>
                <input id="image" name="image" type="url" class="auth-input" value="<?= esc($imageValue) ?>" placeholder="https://example.com/event.jpg">
                <p class="field-hint"><?= esc(lang('App.eventCreateImageHint')) ?></p>
            </div>

            <div class="event-field event-field-full">
                <label for="image_upload" class="auth-label"><?= esc(lang('App.eventCreateImageUploadLabel')) ?></label>
                <input id="image_upload" name="image_upload" type="file" class="auth-input file-input" accept="image/png,image/jpeg,image/webp,image/gif">
                <p class="field-hint"><?= esc(lang('App.eventCreateImageUploadHint')) ?></p>
            </div>

            <div class="event-field event-field-full">
                <div id="image-preview-card" class="image-preview-card <?= $imageValue !== '' ? '' : 'is-empty' ?>">
                    <img id="image-preview" class="image-preview-media" alt="<?= esc(lang('App.eventCreatePreviewAlt')) ?>" <?= $imageValue !== '' ? 'src="' . esc($imageValue, 'attr') . '"' : '' ?>>
                    <div id="image-preview-placeholder" class="image-preview-placeholder"><?= esc(lang('App.noImage')) ?></div>
                </div>
            </div>

            <div class="event-field">
                <label for="capacity" class="auth-label"><?= esc(lang('App.eventCreateCapacityLabel')) ?></label>
                <input id="capacity" name="capacity" type="number" min="1" class="auth-input" value="<?= esc((string) old('capacity', (string) ($event['capacity'] ?? '1'))) ?>" required>
            </div>

            <div class="event-field">
                <label for="event_type" class="auth-label"><?= esc(lang('App.eventCreateTypeLabel')) ?></label>
                <select id="event_type" name="event_type" class="auth-input" required>
                    <option value="free" <?= $selectedType === 'free' ? 'selected' : '' ?>><?= esc(lang('App.freeEvent')) ?></option>
                    <option value="donation" <?= $selectedType === 'donation' ? 'selected' : '' ?>><?= esc(lang('App.eventCreateDonationType')) ?></option>
                </select>
            </div>

            <div class="event-field event-field-full event-datetime-grid">
                <div class="event-datetime-card">
                    <label for="start_date" class="auth-label"><?= esc(lang('App.startDate')) ?></label>
                    <div class="event-datetime-inputs">
                        <input id="start_date" name="start_date" type="date" class="auth-input" value="<?= esc($startDateValue) ?>" required>
                        <select id="start_time" name="start_time" class="auth-input" required>
                            <option value=""><?= esc(lang('App.eventCreateSelectTime')) ?></option>
                            <?php foreach ($timeOptions as $timeOption): ?>
                                <option value="<?= esc($timeOption) ?>" <?= $startTimeValue === $timeOption ? 'selected' : '' ?>><?= esc($timeOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="event-datetime-card">
                    <label for="end_date" class="auth-label"><?= esc(lang('App.endDate')) ?></label>
                    <div class="event-datetime-inputs">
                        <input id="end_date" name="end_date" type="date" class="auth-input" value="<?= esc($endDateValue) ?>" required>
                        <select id="end_time" name="end_time" class="auth-input" required>
                            <option value=""><?= esc(lang('App.eventCreateSelectTime')) ?></option>
                            <?php foreach ($timeOptions as $timeOption): ?>
                                <option value="<?= esc($timeOption) ?>" <?= $endTimeValue === $timeOption ? 'selected' : '' ?>><?= esc($timeOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="event-field">
                <label for="min_donation" class="auth-label"><?= esc(lang('App.eventCreateDonationLabel')) ?></label>
                <input id="min_donation" name="min_donation" type="number" min="0" step="0.01" class="auth-input" value="<?= esc((string) old('min_donation', (string) ($event['min_donation'] ?? ''))) ?>" placeholder="0.00">
                <p class="field-hint"><?= esc(lang('App.eventCreateDonationHint')) ?></p>
            </div>

            <div class="event-field">
                <label for="status" class="auth-label"><?= esc(lang('App.eventCreateStatusLabel')) ?></label>
                <select id="status" name="status" class="auth-input" required>
                    <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>><?= esc(lang('App.eventStatusActive')) ?></option>
                    <option value="inactive" <?= $selectedStatus === 'inactive' ? 'selected' : '' ?>><?= esc(lang('App.eventStatusInactive')) ?></option>
                    <option value="cancelled" <?= $selectedStatus === 'cancelled' ? 'selected' : '' ?>><?= esc(lang('App.eventStatusCancelled')) ?></option>
                </select>
            </div>

            <div class="event-field">
                <span class="auth-label"><?= esc(lang('App.eventCreateBookingsLabel')) ?></span>
                <input type="hidden" name="bookings_enabled" value="0">
                <label class="booking-consent event-toggle-row" for="bookings_enabled">
                    <input id="bookings_enabled" name="bookings_enabled" type="checkbox" value="1" <?= $bookingsEnabled ? 'checked' : '' ?>>
                    <span><?= esc(lang('App.eventCreateBookingsHint')) ?></span>
                </label>
            </div>

            <div class="event-field event-field-full">
                <label for="description" class="auth-label"><?= esc(lang('App.eventCreateDescriptionLabel')) ?></label>
                <textarea id="description" name="description" class="auth-input event-textarea" rows="7"><?= esc((string) old('description', (string) ($event['description'] ?? ''))) ?></textarea>
            </div>

            <div class="event-actions event-field-full">
                <button type="submit" class="book-btn auth-submit"><?= esc($submitLabel) ?></button>
            </div>
        </form>
    </section>
</main>
<script src="<?= base_url('assets/js/event-create.js') ?>?v=<?= esc($assetVersion('assets/js/event-create.js')) ?>"></script>
<?= $this->endSection() ?>

