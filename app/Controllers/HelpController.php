<?php

namespace App\Controllers;

use App\Libraries\HelpAssistant;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * JSON for the help chat (assets/js/help-chat.js): the questions to offer and the answer to one of them.
 * Answers about bookings use the signed-in user's id from the session only.
 */
class HelpController extends BaseController
{
    public function menu(): ResponseInterface
    {
        return $this->json([
            'signedIn'  => $this->userId() !== null,
            'questions' => HelpAssistant::menu($this->userId(), $this->request->getLocale()),
        ]);
    }

    public function answer(): ResponseInterface
    {
        $id = substr((string) $this->request->getGet('q'), 0, 40);

        $answer = $id === '' ? null : HelpAssistant::answer($id, $this->userId(), $this->request->getLocale());
        if ($answer === null) {
            return $this->json(['error' => lang('App.helpUnknown')], 404);
        }

        return $this->json($answer);
    }

    private function userId(): ?int
    {
        $userId = (int) session()->get('user_id');

        return session()->get('is_logged_in') === true && $userId > 0 ? $userId : null;
    }

    private function json(array $data, int $status = 200): ResponseInterface
    {
        // Personal data: never kept by the browser, a proxy or the service worker.
        return $this->response
            ->setStatusCode($status)
            ->setHeader('Cache-Control', 'no-store, private')
            ->setJSON($data);
    }
}
