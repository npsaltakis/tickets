<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

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

    public function about(): string
    {
        return view('pages/about', [
            'pageTitle' => lang('App.aboutPageTitle'),
        ]);
    }

    public function contact(): string
    {
        return view('pages/contact', [
            'pageTitle' => lang('App.contactPageTitle'),
        ]);
    }

    public function sendContact(): RedirectResponse
    {
        // Honeypot: real users never see or fill this field.
        if (trim((string) $this->request->getPost('website')) !== '') {
            return redirect()->to(base_url('contact'))->with('contact_info', lang('App.contactSent'));
        }

        $name    = trim((string) $this->request->getPost('name'));
        $email   = trim((string) $this->request->getPost('email'));
        $message = trim((string) $this->request->getPost('message'));

        if ($name === '' || $message === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($message) > 5000) {
            return redirect()->back()->withInput()->with('contact_error', lang('App.contactInvalid'));
        }

        $cache = cache();
        $key   = 'contact_rate_' . sha1((string) $this->request->getIPAddress());
        $count = (int) ($cache->get($key) ?? 0);

        if ($count >= 5) {
            return redirect()->back()->withInput()->with('contact_error', lang('App.contactRateLimited'));
        }

        $cache->save($key, $count + 1, 3600);

        $to = trim((string) getenv('ADMIN_NOTIFY_EMAIL')) ?: trim((string) env('email.fromEmail', ''));
        $ok = false;

        if ($to !== '') {
            try {
                $mail = service('email');
                $mail->clear();
                $mail->setTo($to);
                $mail->setReplyTo($email, $name);
                $mail->setSubject('[Contact] ' . mb_substr((string) preg_replace('/[\r\n]+/', ' ', $name), 0, 80));
                $mail->setMailType('html');
                $mail->setMessage('<p><strong>' . esc($name) . '</strong> &lt;' . esc($email) . '&gt;</p><p>' . nl2br(esc($message)) . '</p>');
                $ok = $mail->send(false);
            } catch (\Throwable $exception) {
                log_message('error', 'Contact form email failed: {message}', ['message' => $exception->getMessage()]);
            }
        }

        if (! $ok) {
            return redirect()->back()->withInput()->with('contact_error', lang('App.contactFailed'));
        }

        return redirect()->to(base_url('contact'))->with('contact_info', lang('App.contactSent'));
    }

    public function terms(): string
    {
        return view('pages/terms', [
            'pageTitle' => lang('App.termsPageTitle'),
        ]);
    }
}
