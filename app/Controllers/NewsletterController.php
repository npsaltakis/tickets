<?php

namespace App\Controllers;

use App\Libraries\EmailQueue;
use App\Libraries\Newsletter;
use App\Models\EventModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class NewsletterController extends BaseController
{
    /**
     * Unsubscribe confirmation page. The change itself is a POST, so link scanners in mail clients
     * cannot unsubscribe people by just opening the link.
     */
    public function unsubscribeForm(string $token): string
    {
        $user = $this->findByToken($token);

        return view('newsletter/unsubscribe', [
            'token'      => $token,
            'valid'      => $user !== null,
            'subscribed' => $user !== null && (int) $user['newsletter_opt_in'] === 1,
            'done'       => false,
            'pageTitle'  => lang('App.newsletterUnsubscribeTitle'),
        ]);
    }

    public function unsubscribe(string $token): string
    {
        $user = $this->findByToken($token);
        if ($user !== null) {
            (new UserModel())->setNewsletter((int) $user['id'], false);
        }

        return view('newsletter/unsubscribe', [
            'token'      => $token,
            'valid'      => $user !== null,
            'subscribed' => false,
            'done'       => $user !== null,
            'pageTitle'  => lang('App.newsletterUnsubscribeTitle'),
        ]);
    }

    public function index(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $newsletter = new Newsletter();

        return view('admin/newsletter', [
            'subscriberCount' => count($newsletter->subscribers()),
            'events'          => $this->upcomingEvents(),
            'batchSize'       => EmailQueue::batchSize(),
            'campaigns'       => $newsletter->campaigns(),
            'pageTitle'       => lang('App.newsletterTitle'),
        ]);
    }

    public function campaign(int $campaignId): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $newsletter = new Newsletter();
        $campaign   = $newsletter->campaign($campaignId);
        if ($campaign === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $status = (string) $this->request->getGet('status');
        if (! in_array($status, ['sent', 'pending', 'failed'], true)) {
            $status = '';
        }

        $all    = $newsletter->recipients($campaignId);
        $counts = ['sent' => 0, 'pending' => 0, 'failed' => 0];
        foreach ($all as $row) {
            $counts[$this->rowStatus($row)]++;
        }

        return view('admin/newsletter_campaign', [
            'campaign'  => $campaign,
            'rows'      => $status === '' ? $all : array_values(array_filter($all, fn (array $row): bool => $this->rowStatus($row) === $status)),
            'counts'    => $counts,
            'status'    => $status,
            'maxTries'  => EmailQueue::MAX_ATTEMPTS,
            'pageTitle' => lang('App.newsletterCampaignTitle'),
        ]);
    }

    public function retry(int $campaignId): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $newsletter = new Newsletter();
        $retried    = $newsletter->retryFailed($campaignId);
        $sent       = $retried > 0 ? (new EmailQueue())->flush(EmailQueue::batchSize()) : 0;

        $this->logAdminAction('newsletter_retry', 'system', ['campaign_id' => $campaignId, 'retried' => $retried]);

        return redirect()->to(base_url('admin/newsletter/' . $campaignId))->with('newsletter_info', strtr(lang('App.newsletterRetried'), [
            '{n}'    => (string) $retried,
            '{sent}' => (string) $sent,
        ]));
    }

    private function rowStatus(array $row): string
    {
        if ($row['sent_at'] !== null) {
            return 'sent';
        }

        return (int) $row['attempts'] >= EmailQueue::MAX_ATTEMPTS ? 'failed' : 'pending';
    }

    public function send(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $subject  = trim((string) $this->request->getPost('subject'));
        $message  = trim((string) $this->request->getPost('message'));
        $eventId  = (int) $this->request->getPost('event_id');
        $testOnly = $this->request->getPost('test_only') === '1';
        $back     = redirect()->to(base_url('admin/newsletter'))->withInput();

        if ($subject === '' || $message === '') {
            return $back->with('newsletter_error', lang('App.newsletterRequired'));
        }

        $actionUrl = '';
        if ($eventId > 0) {
            $event = (new EventModel())->where('id', $eventId)->where('deleted_at', null)->first();
            if ($event !== null) {
                $actionUrl = base_url('events/' . $event['slug']);
            }
        }

        $newsletter = new Newsletter();
        $queue      = new EmailQueue();

        if ($testOnly) {
            $admin = (new UserModel())->find((int) session()->get('user_id'));
            $queue->push((string) ($admin['email'] ?? ''), '[TEST] ' . $subject, $this->buildMessage($newsletter, $subject, $message, (string) ($admin['first_name'] ?? ''), $actionUrl, base_url('profile')));
            $queue->flush(1);

            return $back->with('newsletter_info', lang('App.newsletterTestSent'));
        }

        $campaignId = $newsletter->createCampaign($subject, $eventId > 0 ? $eventId : null, (int) session()->get('user_id'));

        $queued = 0;
        foreach ($newsletter->subscribers() as $subscriber) {
            $queue->push(
                (string) $subscriber['email'],
                $subject,
                $this->buildMessage($newsletter, $subject, $message, (string) $subscriber['first_name'], $actionUrl, $newsletter->unsubscribeUrl((string) $subscriber['newsletter_token'])),
                $campaignId
            );
            $queued++;
        }
        $newsletter->setRecipients($campaignId, $queued);

        // Only one small batch now; the cron job (`php spark emails:process`) sends the rest at the host's pace.
        $sent = $queue->flush(EmailQueue::batchSize());

        $this->logAdminAction('newsletter_send', 'system', ['campaign_id' => $campaignId, 'subject' => $subject, 'recipients' => $queued, 'event_id' => $eventId ?: null]);

        return redirect()->to(base_url('admin/newsletter/' . $campaignId))->with('newsletter_info', strtr(lang('App.newsletterQueued'), [
            '{queued}' => (string) $queued,
            '{sent}'   => (string) $sent,
        ]));
    }

    private function buildMessage(Newsletter $newsletter, string $subject, string $message, string $firstName, string $actionUrl, string $unsubscribeUrl): string
    {
        return $newsletter->buildEmail(
            $subject,
            $message,
            $firstName !== '' ? lang('App.newsletterGreeting', [$firstName]) : '',
            $actionUrl,
            lang('App.newsletterEventButton'),
            $unsubscribeUrl,
            lang('App.newsletterUnsubscribeFooter')
        );
    }

    private function findByToken(string $token): ?array
    {
        if (preg_match('/^[a-f0-9]{48}$/', $token) !== 1) {
            return null;
        }

        return (new UserModel())->where('newsletter_token', $token)->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function upcomingEvents(): array
    {
        return (new EventModel())
            ->select('id, title, start_date')
            ->where('deleted_at', null)
            ->where('status', 'active')
            ->where('is_private', 0)
            ->groupStart()->where('end_date >=', date('Y-m-d H:i:s'))->orWhere('end_date', null)->groupEnd()
            ->orderBy('start_date', 'ASC')
            ->findAll(50);
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }
}
