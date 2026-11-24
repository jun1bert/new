<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeploymentCheck extends Command
{
    protected $signature = 'app:deployment-check';
    protected $description = 'Read-only checks for production configuration, database, assets, and demo data';

    public function handle(): int
    {
        $checks = [];
        $check = function (string $label, bool $passed, string $instruction) use (&$checks) {
            $checks[] = [$passed ? 'PASS' : 'FAIL', $label, $passed ? 'OK' : $instruction];
        };
        $check('Production environment', app()->isProduction(), 'Set APP_ENV=production on the hosting server.');
        $check('Debug disabled', !config('app.debug'), 'Set APP_DEBUG=false.');
        $url = config('app.url');
        $check('Public HTTPS URL', parse_url($url, PHP_URL_SCHEME) === 'https'
            && !in_array(parse_url($url, PHP_URL_HOST), [null, 'localhost', '127.0.0.1', '::1']), 'Set APP_URL to your real https:// domain.');
        $check('Application key', filled(config('app.key')), 'Generate a unique APP_KEY on the first production install; preserve it on updates.');
        $check('Secure session cookies', (bool) config('session.secure'), 'Set SESSION_SECURE_COOKIE=true after HTTPS is enabled.');
        $check('Real mail transport', !in_array(config('mail.default'), ['log', 'array']), 'Configure and test SMTP for password reset emails.');
        $check('Production asset manifest', is_file(public_path('build/manifest.json')), 'Build assets with npm ci and npm run build, then upload public/build.');
        $check('Development server marker absent', !is_file(public_path('hot')), 'Exclude public/hot from deployment.');
        $check('Storage writable', is_writable(storage_path()) && is_writable(storage_path('framework/views')), 'Allow the hosting PHP user to write storage and storage/framework/views.');
        $check('Bootstrap cache writable', is_writable(base_path('bootstrap/cache')), 'Allow the hosting PHP user to write bootstrap/cache.');
        try {
            DB::connection()->getPdo();
            $check('Database connection', true, '');
            $migrator = app('migrator');
            $pending = !$migrator->repositoryExists() || array_diff(array_keys($migrator->getMigrationFiles(database_path('migrations'))), $migrator->getRepository()->getRan());
            $check('Database migrations', !$pending, 'Back up the database, then run php artisan migrate --force.');
            $demoUsers = Schema::hasTable('users') && User::where(fn ($query) => $query->where('email', 'like', '%@demo.lunelle.test')
                ->orWhere('email', 'like', '%@demo.shafers.test')->orWhere('email', 'like', 'guest-demo-%@booking.local')->orWhere('email', 'test@example.com'))->exists();
            $demoServices = Schema::hasTable('services') && Service::where('name', 'like', 'Demo %')->exists();
            $check('Demo records absent', !$demoUsers && !$demoServices, 'Use a clean production database with a real owner account; do not import demo records.');
            $ownerExists = Schema::hasTable('users') && User::whereIn('user_type', ['owner', 'administrator'])->where('is_active', true)
                ->where('email', 'not like', '%@demo.lunelle.test')->where('email', 'not like', '%@demo.shafers.test')->where('email', '!=', 'test@example.com')->exists();
            $check('Real owner account', $ownerExists, 'Run php artisan app:create-owner to create a real owner.');
        } catch (\Throwable $exception) {
            $check('Database checks', false, 'Database validation could not finish. Check the connection and migrations privately in the server logs.');
        }
        $this->table(['Result', 'Check', 'Action'], $checks);
        $failures = collect($checks)->where(0, 'FAIL')->count();
        $this->line('This check does not verify public DNS, TLS, document-root isolation, mail delivery, backups, or browser behavior.');
        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
