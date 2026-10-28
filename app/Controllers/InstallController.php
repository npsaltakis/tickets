<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

/**
 * One-time web installer for hosts without SSH (FTP + control panel only).
 * Requires `install.token` in .env, runs all migrations and creates the first admin.
 * Locks itself with writable/install.lock, and stays closed while an admin already exists.
 */
class InstallController extends BaseController
{
    private const LOCK_FILE = 'install.lock';

    private const REQUIRED_EXTENSIONS = ['intl', 'mbstring', 'mysqli', 'gd', 'curl', 'zlib', 'json'];

    public function index(): string
    {
        $this->abortIfInstalled();

        $checks = $this->runChecks();

        return view('install/index', [
            'checks'    => $checks,
            'canSubmit' => ! in_array(false, array_column(array_filter($checks, static fn (array $c): bool => $c['required']), 'ok'), true),
            'pageTitle' => lang('App.installTitle'),
        ]);
    }

    public function run(): RedirectResponse
    {
        $this->abortIfInstalled();

        $back = redirect()->to(base_url('install'))->withInput();

        $expectedToken = (string) env('install.token', '');
        $givenToken    = (string) $this->request->getPost('install_token');
        if (strlen($expectedToken) < 16 || ! hash_equals($expectedToken, $givenToken)) {
            return $back->with('install_error', lang('App.installTokenInvalid'));
        }

        foreach ($this->runChecks() as $check) {
            if ($check['required'] && ! $check['ok']) {
                return $back->with('install_error', lang('App.installChecksFailed'));
            }
        }

        $firstName = trim((string) $this->request->getPost('first_name'));
        $lastName  = trim((string) $this->request->getPost('last_name'));
        $email     = strtolower(trim((string) $this->request->getPost('email')));
        $password  = (string) $this->request->getPost('password');
        $confirm   = (string) $this->request->getPost('confirm_password');

        if ($firstName === '' || $lastName === '') {
            return $back->with('install_error', lang('App.installNameRequired'));
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $back->with('install_error', lang('App.invalidEmail'));
        }
        if (strlen($password) < 8) {
            return $back->with('install_error', lang('App.passwordTooShort'));
        }
        if ($password !== $confirm) {
            return $back->with('install_error', lang('App.passwordsDoNotMatch'));
        }

        try {
            service('migrations')->latest();
        } catch (Throwable $exception) {
            log_message('error', 'Installer migration failed: {message}', ['message' => $exception->getMessage()]);

            return $back->with('install_error', lang('App.installMigrationFailed') . ' ' . $exception->getMessage());
        }

        $userModel = new UserModel();
        $existing  = $userModel->where('email', $email)->first();
        $payload   = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'role'       => 'admin',
            'status'     => 'active',
        ];

        $saved = empty($existing)
            ? $userModel->insert($payload) !== false
            : $userModel->update((int) $existing['id'], $payload);

        if (! $saved) {
            return $back->with('install_error', implode(' ', $userModel->errors()) ?: lang('App.installAdminFailed'));
        }

        file_put_contents(WRITEPATH . self::LOCK_FILE, 'Installed at ' . date(DATE_ATOM) . ' by ' . $email . "\n");

        return redirect()->to(base_url('login'))->with('login_info', lang('App.installDone'));
    }

    /**
     * The installer exists only until the first admin is created; afterwards it is a 404.
     */
    private function abortIfInstalled(): void
    {
        if (is_file(WRITEPATH . self::LOCK_FILE)) {
            throw PageNotFoundException::forPageNotFound();
        }

        try {
            $db = db_connect();
            if ($db->tableExists('users') && $db->table('users')->where('role', 'admin')->countAllResults() > 0) {
                throw PageNotFoundException::forPageNotFound();
            }
        } catch (PageNotFoundException $exception) {
            throw $exception;
        } catch (Throwable) {
            // No database yet: the checks below report it.
        }
    }

    /**
     * @return list<array{label: string, ok: bool, required: bool, detail: string}>
     */
    private function runChecks(): array
    {
        $checks = [];

        $checks[] = [
            'label'    => 'PHP >= 8.2',
            'ok'       => version_compare(PHP_VERSION, '8.2.0', '>='),
            'required' => true,
            'detail'   => PHP_VERSION,
        ];

        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            $checks[] = [
                'label'    => 'PHP extension: ' . $extension,
                'ok'       => extension_loaded($extension),
                'required' => true,
                'detail'   => extension_loaded($extension) ? 'ok' : lang('App.installMissing'),
            ];
        }

        $writable = [
            'writable/cache'        => WRITEPATH . 'cache',
            'writable/logs'         => WRITEPATH . 'logs',
            'writable/session'      => WRITEPATH . 'session',
            'writable/uploads'      => WRITEPATH . 'uploads',
            'public/assets/images'  => FCPATH . 'assets/images',
        ];
        foreach ($writable as $label => $path) {
            $ok = is_dir($path) && is_writable($path);
            $checks[] = [
                'label'    => lang('App.installWritable') . ': ' . $label,
                'ok'       => $ok,
                'required' => true,
                'detail'   => $ok ? 'ok' : lang('App.installNotWritable'),
            ];
        }

        $tokenOk  = strlen((string) env('install.token', '')) >= 16;
        $checks[] = [
            'label'    => '.env install.token',
            'ok'       => $tokenOk,
            'required' => true,
            'detail'   => $tokenOk ? 'ok' : lang('App.installTokenMissing'),
        ];

        $dbDetail = 'ok';
        try {
            db_connect()->query('SELECT 1');
            $dbOk = true;
        } catch (Throwable $exception) {
            $dbOk     = false;
            $dbDetail = $exception->getMessage();
        }
        $checks[] = [
            'label'    => lang('App.installDatabase'),
            'ok'       => $dbOk,
            'required' => true,
            'detail'   => $dbDetail,
        ];

        $baseUrl  = (string) config('App')->baseURL;
        $checks[] = [
            'label'    => 'app.baseURL (HTTPS)',
            'ok'       => str_starts_with($baseUrl, 'https://') && ! str_contains($baseUrl, 'localhost'),
            'required' => false,
            'detail'   => $baseUrl,
        ];

        $checks[] = [
            'label'    => 'CI_ENVIRONMENT = production',
            'ok'       => ENVIRONMENT === 'production',
            'required' => false,
            'detail'   => ENVIRONMENT,
        ];

        return $checks;
    }
}
