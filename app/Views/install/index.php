<?php
/**
 * Standalone page: before .env exists base_url() points to the default localhost URL,
 * so assets and the form use root-relative paths instead of the layout.
 */
$field = static fn (string $key): string => esc((string) ($values[$key] ?? ''), 'attr');
?>
<!doctype html>
<html lang="<?= esc(service('request')->getLocale(), 'attr') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc(lang('App.installTitle')) ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<main class="wrapper">
    <section class="auth-card install-card">
        <h1 class="auth-title"><?= esc(lang('App.installTitle')) ?></h1>
        <p class="subtitle"><?= esc(lang('App.installSubtitle')) ?></p>

        <?php if ($error !== ''): ?>
            <p role="alert" class="auth-error"><?= esc($error) ?></p>
        <?php endif; ?>

        <h2><?= esc(lang('App.installChecksTitle')) ?></h2>
        <ul class="install-checks">
            <?php foreach ($checks as $check): ?>
                <li class="install-check <?= $check['ok'] ? 'is-ok' : ($check['required'] ? 'is-error' : 'is-warning') ?>">
                    <span class="install-check-mark"><?= $check['ok'] ? '&#10003;' : ($check['required'] ? '&#10007;' : '!') ?></span>
                    <strong><?= esc($check['label']) ?></strong>
                    <span class="meta"><?= esc($check['detail']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (! $canSubmit): ?>
            <p role="alert" class="auth-error"><?= esc(lang('App.installChecksFailed')) ?></p>
        <?php else: ?>
            <form method="post" action="/install" class="auth-form" autocomplete="off">
                <?= csrf_field() ?>

                <h2><?= esc(lang('App.installSiteTitle')) ?></h2>
                <label for="base_url" class="auth-label"><?= esc(lang('App.installBaseUrlLabel')) ?></label>
                <input id="base_url" name="base_url" type="url" value="<?= $field('base_url') ?>" class="auth-input" required>

                <label for="site_name" class="auth-label"><?= esc(lang('App.settingsSiteName')) ?></label>
                <input id="site_name" name="site_name" type="text" maxlength="100" value="<?= $field('site_name') ?>" class="auth-input" required>

                <label for="contact_email" class="auth-label"><?= esc(lang('App.installContactEmailLabel')) ?></label>
                <input id="contact_email" name="contact_email" type="email" maxlength="191" value="<?= $field('contact_email') ?>" class="auth-input" autocomplete="email">
                <p class="meta"><?= esc(lang('App.installSiteHint')) ?></p>

                <h2><?= esc(lang('App.installDatabaseTitle')) ?></h2>
                <p class="meta"><?= esc(lang('App.installDatabaseHint')) ?></p>
                <label for="db_host" class="auth-label"><?= esc(lang('App.installDbHostLabel')) ?></label>
                <input id="db_host" name="db_host" type="text" value="<?= $field('db_host') ?>" class="auth-input" required>

                <label for="db_port" class="auth-label"><?= esc(lang('App.installDbPortLabel')) ?></label>
                <input id="db_port" name="db_port" type="number" min="1" max="65535" value="<?= $field('db_port') ?>" class="auth-input" required>

                <label for="db_name" class="auth-label"><?= esc(lang('App.installDbNameLabel')) ?></label>
                <input id="db_name" name="db_name" type="text" value="<?= $field('db_name') ?>" class="auth-input" required>

                <label for="db_user" class="auth-label"><?= esc(lang('App.installDbUserLabel')) ?></label>
                <input id="db_user" name="db_user" type="text" value="<?= $field('db_user') ?>" class="auth-input" required>

                <label for="db_pass" class="auth-label"><?= esc(lang('App.installDbPassLabel')) ?></label>
                <input id="db_pass" name="db_pass" type="password" class="auth-input" autocomplete="new-password">

                <label for="db_prefix" class="auth-label"><?= esc(lang('App.installDbPrefixLabel')) ?></label>
                <input id="db_prefix" name="db_prefix" type="text" value="<?= $field('db_prefix') ?>" class="auth-input" pattern="\w*">

                <h2><?= esc(lang('App.installAdminTitle')) ?></h2>
                <label for="first_name" class="auth-label"><?= esc(lang('App.firstNameLabel')) ?></label>
                <input id="first_name" name="first_name" type="text" value="<?= $field('first_name') ?>" class="auth-input" required>

                <label for="last_name" class="auth-label"><?= esc(lang('App.lastNameLabel')) ?></label>
                <input id="last_name" name="last_name" type="text" value="<?= $field('last_name') ?>" class="auth-input" required>

                <label for="email" class="auth-label"><?= esc(lang('App.emailLabel')) ?></label>
                <input id="email" name="email" type="email" value="<?= $field('email') ?>" class="auth-input" required autocomplete="email">

                <label for="password" class="auth-label"><?= esc(lang('App.passwordLabel')) ?></label>
                <input id="password" name="password" type="password" class="auth-input" required minlength="8" autocomplete="new-password">

                <label for="confirm_password" class="auth-label"><?= esc(lang('App.confirmPasswordLabel')) ?></label>
                <input id="confirm_password" name="confirm_password" type="password" class="auth-input" required minlength="8" autocomplete="new-password">

                <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.installButton')) ?></button>
            </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
