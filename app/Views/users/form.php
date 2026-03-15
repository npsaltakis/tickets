<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <?php
    $isEditMode = (bool) ($isEditMode ?? false);
    $user = is_array($user ?? null) ? $user : [];
    $formAction = $isEditMode ? base_url('users/' . (int) $user['id'] . '/update') : base_url('users');
    ?>
    <a class="back-link" href="<?= base_url('users') ?>">&larr; <?= esc(lang('App.usersBackToList')) ?></a>

    <section class="event-form-card users-form-card">
        <div class="event-form-header">
            <h1 class="auth-title"><?= esc($isEditMode ? lang('App.usersEditTitle') : lang('App.usersCreateTitle')) ?></h1>
            <p class="subtitle"><?= esc($isEditMode ? lang('App.usersEditSubtitle') : lang('App.usersCreateSubtitle')) ?></p>
        </div>

        <?php if (session()->getFlashdata('users_error')): ?>
            <p class="auth-error"><?= esc((string) session()->getFlashdata('users_error')) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= $formAction ?>" class="event-form-grid users-form-grid">
            <?= csrf_field() ?>

            <div class="event-field">
                <label for="first_name" class="auth-label"><?= esc(lang('App.firstNameLabel')) ?></label>
                <input id="first_name" name="first_name" type="text" class="auth-input" value="<?= esc((string) old('first_name', (string) ($user['first_name'] ?? ''))) ?>" required>
            </div>

            <div class="event-field">
                <label for="last_name" class="auth-label"><?= esc(lang('App.lastNameLabel')) ?></label>
                <input id="last_name" name="last_name" type="text" class="auth-input" value="<?= esc((string) old('last_name', (string) ($user['last_name'] ?? ''))) ?>" required>
            </div>

            <div class="event-field">
                <label for="email" class="auth-label"><?= esc(lang('App.emailLabel')) ?></label>
                <input id="email" name="email" type="email" class="auth-input" value="<?= esc((string) old('email', (string) ($user['email'] ?? ''))) ?>" required>
            </div>

            <div class="event-field">
                <label for="role" class="auth-label"><?= esc(lang('App.usersRole')) ?></label>
                <?php $selectedRole = (string) old('role', (string) ($user['role'] ?? 'client')); ?>
                <select id="role" name="role" class="auth-input" required>
                    <option value="client" <?= $selectedRole === 'client' ? 'selected' : '' ?>><?= esc(lang('App.usersRoleClient')) ?></option>
                    <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>><?= esc(lang('App.usersRoleAdmin')) ?></option>
                </select>
            </div>

            <div class="event-field event-field-full">
                <label for="password" class="auth-label"><?= esc(lang('App.passwordLabel')) ?></label>
                <input id="password" name="password" type="password" class="auth-input" <?= $isEditMode ? '' : 'required' ?> autocomplete="new-password">
                <p class="field-hint"><?= esc($isEditMode ? lang('App.usersPasswordHintEdit') : lang('App.usersPasswordHintCreate')) ?></p>
            </div>

            <div class="event-actions event-field-full">
                <button type="submit" class="book-btn auth-submit"><?= esc($isEditMode ? lang('App.usersSaveButton') : lang('App.usersCreateButton')) ?></button>
            </div>
        </form>
    </section>
</main>
<?= $this->endSection() ?>
