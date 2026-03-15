<?php

namespace App\Controllers;

class PagesController extends BaseController
{
    public function gdpr(): string
    {
        return view('pages/gdpr', [
            'pageTitle' => lang('App.gdprPageTitle'),
        ]);
    }

    public function privacy(): string
    {
        return view('pages/privacy', [
            'pageTitle' => lang('App.privacyPageTitle'),
        ]);
    }

    public function terms(): string
    {
        return view('pages/terms', [
            'pageTitle' => lang('App.termsPageTitle'),
        ]);
    }
}
