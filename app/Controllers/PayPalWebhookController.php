<?php

namespace App\Controllers;

use App\Libraries\BookingService;
use App\Libraries\PayPalClient;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Receives PayPal webhooks so payments and refunds stay in sync even when the
 * customer never returns to the site (closed browser, network drop, refund from the PayPal dashboard).
 */
class PayPalWebhookController extends EventBaseController
{
    public function handle(): ResponseInterface
    {
        $rawBody = (string) $this->request->getBody();
        $headers = [];

        foreach (['paypal-auth-algo', 'paypal-cert-url', 'paypal-transmission-id', 'paypal-transmission-sig', 'paypal-transmission-time'] as $name) {
            $headers[$name] = $this->request->getHeaderLine($name);
        }

        if (! (new PayPalClient())->verifyWebhook($headers, $rawBody)) {
            log_message('warning', 'PayPal webhook rejected: signature verification failed.');

            return $this->response->setStatusCode(401)->setJSON(['status' => 'invalid signature']);
        }

        $event    = json_decode($rawBody, true);
        $type     = is_array($event) ? (string) ($event['event_type'] ?? '') : '';
        $resource = is_array($event) && is_array($event['resource'] ?? null) ? $event['resource'] : [];

        try {
            match ($type) {
                'PAYMENT.CAPTURE.COMPLETED' => $this->onCaptureCompleted($resource),
                'PAYMENT.CAPTURE.REFUNDED'  => $this->onCaptureRefunded($resource),
                default                     => null,
            };
        } catch (Throwable $exception) {
            log_message('error', 'PayPal webhook {type} failed: {message}', ['type' => $type, 'message' => $exception->getMessage()]);

            // Non-2xx makes PayPal retry the delivery.
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error']);
        }

        return $this->response->setJSON(['status' => 'ok']);
    }

    private function onCaptureCompleted(array $capture): void
    {
        $customId = (string) ($capture['custom_id'] ?? '');
        if ($customId === '') {
            return;
        }

        $result = (new BookingService())->fulfillCapture($capture, $this->parsePayPalCustomId($customId));

        if ($result['status'] !== 'created' || empty($result['event'])) {
            return;
        }

        $user = (new UserModel())->find((int) $result['user_id']);
        if (! empty($user)) {
            $this->sendBookingConfirmationEmail(
                $result['event'],
                $result['seats'],
                $result['codes'],
                $result['amount'],
                $result['currency'],
                (string) ($user['email'] ?? ''),
                trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))
            );
        }
    }

    private function onCaptureRefunded(array $refund): void
    {
        $captureId = '';

        foreach ((array) ($refund['links'] ?? []) as $link) {
            if (($link['rel'] ?? '') === 'up' && ! empty($link['href'])) {
                $captureId = basename(parse_url((string) $link['href'], PHP_URL_PATH) ?: '');
                break;
            }
        }

        if ($captureId === '') {
            return;
        }

        $eventIds = (new BookingService())->applyExternalRefund(
            $captureId,
            (string) ($refund['id'] ?? ''),
            (float) ($refund['amount']['value'] ?? 0)
        );

        foreach ($eventIds as $eventId) {
            $this->notifyWaitlist($eventId);
        }
    }
}
