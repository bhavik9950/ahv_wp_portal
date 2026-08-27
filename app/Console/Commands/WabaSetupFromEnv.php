<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncTemplatesJob;
use App\Models\Organization;
use App\Models\WhatsappBusinessAccount;
use App\Services\WhatsApp\Data\WabaCredentials;
use App\Services\WhatsApp\Drivers\MetaCloudApiDriver;
use App\Services\WhatsApp\WabaConfigurationService;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

/**
 * Provisions the organization's WhatsApp Business Account from the bootstrap
 * WABA_* values in .env, then pulls its phone numbers and templates from Meta.
 *
 *   php artisan waba:setup
 *   php artisan waba:setup --org=1 --waba-id=123456789012345
 *
 * The DB record (encrypted) is the source of truth after this — .env is only the
 * bootstrap seed.
 */
class WabaSetupFromEnv extends Command
{
    protected $signature = 'waba:setup {--org= : Organization id (defaults to the only/oldest org)} {--waba-id= : Override the WhatsApp Business Account id} {--name=}';

    protected $description = 'Configure the WABA account from .env bootstrap values and sync numbers + templates';

    public function handle(
        WabaConfigurationService $config,
        MetaCloudApiDriver $driver,
        TenantContext $tenant,
        PermissionRegistrar $permissions,
    ): int {
        $boot = (array) config('services.whatsapp.bootstrap');
        $token = (string) ($boot['access_token'] ?? '');
        $phoneNumberId = (string) ($boot['phone_number_id'] ?? '');

        if ($token === '') {
            $this->error('WABA_ACCESS_TOKEN is empty in .env.');

            return self::FAILURE;
        }

        $org = $this->option('org')
            ? Organization::query()->withoutGlobalScopes()->findOrFail($this->option('org'))
            : Organization::query()->withoutGlobalScopes()->oldest('id')->firstOrFail();

        $tenant->set($org);
        $permissions->setPermissionsTeamId($org->getKey());
        $this->info("Organization: {$org->name} (#{$org->getKey()})");

        // Resolve the WABA id.
        $wabaId = (string) ($this->option('waba-id') ?: $boot['business_account_id'] ?: '');
        if ($wabaId === '') {
            $probe = new WabaCredentials(
                accessToken: $token, wabaId: '', phoneNumberId: $phoneNumberId ?: null,
                apiVersion: (string) config('services.whatsapp.api_version'),
                baseUrl: (string) config('services.whatsapp.base_url'),
            );
            $ids = $driver->discoverWabaIds($probe);
            if (count($ids) === 1) {
                $wabaId = $ids[0];
                $this->info("Discovered WABA id from token: {$wabaId}");
            } elseif (count($ids) > 1) {
                $this->warn('Token manages multiple WABAs: '.implode(', ', $ids));
                $wabaId = (string) $this->choice('Which one?', $ids);
            } else {
                $this->error('Could not discover the WABA id. Pass --waba-id=... or set WABA_BUSINESS_ACCOUNT_ID.');

                return self::FAILURE;
            }
        }

        $existing = WhatsappBusinessAccount::query()->where('waba_id', $wabaId)->first();
        $defaultName = $existing !== null ? $existing->name : (string) config('app.name');

        $account = $config->upsert([
            'name' => $this->option('name') ?: $defaultName,
            'waba_id' => $wabaId,
            'meta_business_account_id' => $boot['meta_business_id'] ?: null,
            'app_id' => $boot['app_id'] ?: null,
            'access_token' => $token,
            'app_secret' => $boot['app_secret'] ?: null,
            'webhook_verify_token' => $boot['webhook_verify_token'] ?: null,
            'default_country_code' => (string) config('services.whatsapp.default_country_code'),
        ], $existing);

        $this->info('WABA account saved (id '.$account->getKey().').');

        // Phone numbers first — the connection check needs a default number.
        try {
            $n = $config->syncPhoneNumbers($account->fresh());
            $this->info("Synced {$n} phone number(s).");
        } catch (\Throwable $e) {
            $this->warn('Phone number sync failed: '.$e->getMessage());
        }

        // Templates
        try {
            SyncTemplatesJob::dispatchSync($account->getKey());
            $count = $account->templates()->count();
            $this->info("Synced {$count} template(s) from Meta.");
        } catch (\Throwable $e) {
            $this->warn('Template sync failed: '.$e->getMessage());
        }

        // Connection checks
        $this->line('');
        $this->line('Connection checks:');
        foreach ($config->runConnectionChecks($account->fresh()) as $check) {
            $this->line(sprintf('  [%s] %s — %s', $check->passed ? 'OK' : 'FAIL', $check->label, $check->message));
        }

        $this->line('');
        $this->info('Done. Open WhatsApp → Settings / Templates in the portal.');

        return self::SUCCESS;
    }
}
