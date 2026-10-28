<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Database;
use Throwable;

/**
 * One-time web installer for hosts without SSH (FTP + control panel only).
 * Writes .env (site URL + database), runs all migrations and creates the first admin.
 * Locks itself with writable/install.lock, and stays closed while an admin already exists.
 */
class InstallController extends BaseController
{
    private const LOCK_FILE = 'install.lock';

    private const REQUIRED_EXTENSIONS = ['intl', 'mbstring', 'mysqli', 'gd', 'curl', 'zlib', 'json'];

    public function index(): string
    {
        $this->abortIfInstalled();

        return $this->renderForm($this->defaults());
    }

    public function run(): RedirectResponse|string
    {
        $this->abortIfInstalled();

        $input = [
            'base_url'    => trim((string) $this->request->getPost('base_url')),
            'db_host'     => trim((string) $this->request->getPost('db_host')),
            'db_port'     => (int) $this->request->getPost('db_port'),
            'db_name'     => trim((string) $this->request->getPost('db_name')),
            'db_user'     => trim((string) $this->request->getPost('db_user')),
            'db_pass'     => (string) $this->request->getPost('db_pass'),
            'db_prefix'   => trim((string) $this->request->getPost('db_prefix')),
            'first_name'  => trim((string) $this->request->getPost('first_name')),
            'last_name'   => trim((string) $this->request->getPost('last_name')),
            'email'       => strtolower(trim((string) $this->request->getPost('email'))),
        ];
        $password = (string) $this->request->getPost('password');
        $confirm  = (string) $this->request->getPost('confirm_password');

        foreach ($this->runChecks() as $check) {
            if ($check['required'] && ! $check['ok']) {
                return $this->renderForm($input, lang('App.installChecksFailed'));
            }
        }

        if (! filter_var($input['base_url'], FILTER_VALIDATE_URL) || ! preg_match('#^https?://#', $input['base_url'])) {
            return $this->renderForm($input, lang('App.installBaseUrlInvalid'));
        }
        $input['base_url'] = rtrim($input['base_url'], '/') . '/';

        if ($input['db_host'] === '' || $input['db_name'] === '' || $input['db_user'] === '' || preg_match('/^\w*$/', $input['db_prefix']) !== 1) {
            return $this->renderForm($input, lang('App.installDatabaseRequired'));
        }
        if ($input['first_name'] === '' || $input['last_name'] === '') {
            return $this->renderForm($input, lang('App.installNameRequired'));
        }
        if (! filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->renderForm($input, lang('App.invalidEmail'));
        }
        if (strlen($password) < 8) {
            return $this->renderForm($input, lang('App.passwordTooShort'));
        }
        if ($password !== $confirm) {
            return $this->renderForm($input, lang('App.passwordsDoNotMatch'));
        }

        // Point the default connection at the submitted database for the rest of this request.
        $dbConfig          = config(\Config\Database::class);
        $dbConfig->default = array_merge($dbConfig->default, [
            'hostname' => $input['db_host'],
            'port'     => $input['db_port'] > 0 ? $input['db_port'] : 3306,
            'database' => $input['db_name'],
            'username' => $input['db_user'],
            'password' => $input['db_pass'],
            'DBPrefix' => $input['db_prefix'],
            'DBDebug'  => true,
        ]);

        try {
            Database::connect(null, false)->query('SELECT 1');
        } catch (Throwable $exception) {
            return $this->renderForm($input, lang('App.installDatabaseFailed') . ' ' . $exception->getMessage());
        }

        if (file_put_contents(ROOTPATH . '.env', $this->buildEnv($input)) === false) {
            return $this->renderForm($input, lang('App.installEnvWriteFailed'));
        }

        try {
            service('migrations')->latest();
        } catch (Throwable $exception) {
            log_message('error', 'Installer migration failed: {message}', ['message' => $exception->getMessage()]);

            return $this->renderForm($input, lang('App.installMigrationFailed') . ' ' . $exception->getMessage());
        }

        $userModel = new UserModel();
        $existing  = $userModel->where('email', $input['email'])->first();
        $payload   = [
            'first_name' => $input['first_name'],
            'last_name'  => $input['last_name'],
            'email'      => $input['email'],
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'role'       => 'admin',
            'status'     => 'active',
        ];

        $saved = empty($existing)
            ? $userModel->insert($payload) !== false
            : $userModel->update((int) $existing['id'], $payload);

        if (! $saved) {
            return $this->renderForm($input, implode(' ', $userModel->errors()) ?: lang('App.installAdminFailed'));
        }

        file_put_contents(WRITEPATH . self::LOCK_FILE, 'Installed at ' . date(DATE_ATOM) . ' by ' . $input['email'] . "\n");

        session()->setFlashdata('login_info', lang('App.installDone'));

        // base_url() still holds the pre-install value in this request, so use the new URL directly.
        return redirect()->to($input['base_url'] . 'login');
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
            // Not shared: a failed connection must not be cached before the form's database is set.
            $db = Database::connect(null, false);
            if ($db->tableExists('users') && $db->table('users')->where('role', 'admin')->countAllResults() > 0) {
                throw PageNotFoundException::forPageNotFound();
            }
        } catch (PageNotFoundException $exception) {
            throw $exception;
        } catch (Throwable) {
            // No usable database yet: that is what the installer is for.
        }
    }

    /**
     * @return array<string, int|string>
     */
    private function defaults(): array
    {
        $db   = config(\Config\Database::class)->default;
        $host = (string) $this->request->getServer('HTTP_HOST');

        return [
            'base_url'   => is_file(ROOTPATH . '.env') ? (string) config('App')->baseURL : ($this->request->isSecure() ? 'https' : 'http') . '://' . $host . '/',
            'db_host'    => (string) ($db['hostname'] ?: 'localhost'),
            'db_port'    => (int) ($db['port'] ?: 3306),
            'db_name'    => (string) $db['database'],
            'db_user'    => (string) $db['username'],
            'db_pass'    => '',
            'db_prefix'  => (string) ($db['DBPrefix'] ?: 'tickets_'),
            'first_name' => '',
            'last_name'  => '',
            'email'      => '',
        ];
    }

    private function renderForm(array $values, string $error = ''): string
    {
        $checks = $this->runChecks();

        return view('install/index', [
            'checks'    => $checks,
            'canSubmit' => ! in_array(false, array_column(array_filter($checks, static fn (array $c): bool => $c['required']), 'ok'), true),
            'values'    => $values,
            'error'     => $error,
        ]);
    }

    private function buildEnv(array $input): string
    {
        $quote = static fn (string $value): string => "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";

        return implode("\n", [
            '# Written by the web installer on ' . date(DATE_ATOM) . '.',
            '# Fill in email, Turnstile and PayPal below, then save.',
            '',
            'CI_ENVIRONMENT = production',
            '',
            'app.baseURL = ' . $quote($input['base_url']),
            "app.indexPage = ''",
            '',
            'database.default.hostname = ' . $quote($input['db_host']),
            'database.default.database = ' . $quote($input['db_name']),
            'database.default.username = ' . $quote($input['db_user']),
            'database.default.password = ' . $quote($input['db_pass']),
            'database.default.DBDriver = MySQLi',
            'database.default.DBPrefix = ' . $quote($input['db_prefix']),
            'database.default.port = ' . ($input['db_port'] > 0 ? $input['db_port'] : 3306),
            '',
            '# email.fromEmail = noreply@example.com',
            "# email.fromName = 'Tickets'",
            '# email.protocol = smtp',
            '# email.SMTPHost =',
            '# email.SMTPUser =',
            '# email.SMTPPass =',
            '# email.SMTPPort = 587',
            '# email.SMTPCrypto = tls',
            '# email.mailType = html',
            '# ADMIN_NOTIFY_EMAIL =',
            '',
            '# turnstile.siteKey =',
            '# turnstile.secretKey =',
            '',
            '# PayPal sandbox for testing; live: PAYPAL_BASE_URL = https://api-m.paypal.com',
            '# paypal.clientId =',
            '# paypal.secret =',
            '# PAYPAL_BASE_URL = https://api-m.sandbox.paypal.com',
            '# paypal.webhookId =',
            '',
        ]);
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

        $envPath  = ROOTPATH . '.env';
        $envOk    = is_file($envPath) ? is_writable($envPath) : is_writable(ROOTPATH);
        $checks[] = [
            'label'    => lang('App.installWritable') . ': .env',
            'ok'       => $envOk,
            'required' => true,
            'detail'   => $envOk ? 'ok' : lang('App.installNotWritable'),
        ];

        $writable = [
            'writable/cache'       => WRITEPATH . 'cache',
            'writable/logs'        => WRITEPATH . 'logs',
            'writable/session'     => WRITEPATH . 'session',
            'writable/uploads'     => WRITEPATH . 'uploads',
            'public/assets/images' => FCPATH . 'assets/images',
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

        return $checks;
    }
}
