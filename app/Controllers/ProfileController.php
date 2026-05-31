<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class ProfileController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index(): string|RedirectResponse
    {
        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'))->with('login_info', lang('App.bookingLoginRequired'));
        }

        $user = $this->userModel->find((int) session()->get('user_id'));
        if ($user === null) {
            return redirect()->to(base_url('/'));
        }

        return view('auth/profile', [
            'user'      => $user,
            'pageTitle' => lang('App.profilePageTitle'),
        ]);
    }

    public function update(): RedirectResponse
    {
        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'));
        }

        $userId = (int) session()->get('user_id');
        $user   = $this->userModel->find($userId);
        if ($user === null) {
            return redirect()->to(base_url('/'));
        }

        $firstName   = trim((string) $this->request->getPost('first_name'));
        $lastName    = trim((string) $this->request->getPost('last_name'));
        $currentPass = (string) $this->request->getPost('current_password');
        $newPass     = (string) $this->request->getPost('new_password');
        $confirmPass = (string) $this->request->getPost('confirm_password');

        if ($firstName === '' || $lastName === '') {
            return redirect()->back()->withInput()->with('profile_error', lang('App.profileNameRequired'));
        }

        $payload = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
        ];

        if ($newPass !== '') {
            if ($currentPass === '' || ! password_verify($currentPass, (string) ($user['password'] ?? ''))) {
                return redirect()->back()->withInput()->with('profile_error', lang('App.profileCurrentPasswordWrong'));
            }
            if (strlen($newPass) < 8) {
                return redirect()->back()->withInput()->with('profile_error', lang('App.passwordTooShort'));
            }
            if ($newPass !== $confirmPass) {
                return redirect()->back()->withInput()->with('profile_error', lang('App.profilePasswordMismatch'));
            }
            $payload['password'] = password_hash($newPass, PASSWORD_DEFAULT);
        }

        $this->userModel->update($userId, $payload);

        session()->set([
            'user_name' => trim($firstName . ' ' . $lastName),
        ]);

        return redirect()->to(base_url('profile'))->with('profile_info', lang('App.profileUpdated'));
    }
}
