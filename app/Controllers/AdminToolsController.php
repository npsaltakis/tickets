<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class AdminToolsController extends BaseController
{
    public function sendTestEmail(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        $recipient = trim((string) session()->get('user_email'));
        if ($recipient === '') {
            return redirect()->back()->with('event_error', lang('App.adminTestEmailMissingRecipient'));
        }

        try {
            $emailService = service('email');
            $emailService->setTo($recipient);
            $emailService->setSubject($this->bilingualSubject('App.adminTestEmailSubject'));
            $emailService->setMailType('html');
            $emailService->setMessage($this->buildBilingualActionEmailHtml(
                [
                    $this->localizedLine('App.adminTestEmailGreeting', [], 'el'),
                    $this->localizedLine('App.adminTestEmailIntro', [], 'el'),
                    $this->localizedLine('App.adminTestEmailFooter', [], 'el'),
                ],
                [
                    $this->localizedLine('App.adminTestEmailGreeting', [], 'en'),
                    $this->localizedLine('App.adminTestEmailIntro', [], 'en'),
                    $this->localizedLine('App.adminTestEmailFooter', [], 'en'),
                ],
                base_url('/'),
                $this->localizedLine('App.adminTestEmailButton', [], 'el'),
                $this->localizedLine('App.adminTestEmailButton', [], 'en'),
                $this->bilingualSubject('App.adminTestEmailSubject')
            ));

            if (! $emailService->send()) {
                return redirect()->back()->with('event_error', lang('App.adminTestEmailFailed'));
            }
        } catch (Throwable) {
            return redirect()->back()->with('event_error', lang('App.adminTestEmailFailed'));
        }

        $this->logAdminAction('admin_test_email', 'system', [
            'recipient' => $recipient,
        ]);

        return redirect()->back()->with('event_info', lang('App.adminTestEmailSuccess'));
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }
}
