<?php helper('url'); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'Ticketing System') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/styles.css') ?>">
</head>
<body>
<header class="top-nav">
    <div class="top-nav-inner">
        <div class="top-nav-left">
            <a class="brand" href="<?= base_url('/') ?>">Ticketing System</a>
            <nav aria-label="Main navigation">
                <ul class="menu">
                    <li><a class="menu-link is-active" href="<?= base_url('/') ?>">Κεντρική</a></li>
                </ul>
            </nav>
        </div>

        <div class="lang-switcher">
            <select id="language" name="language">
                <option value="gr" selected>GR</option>
                <option value="en">EN</option>
            </select>
        </div>
    </div>
</header>
