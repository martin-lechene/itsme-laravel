<?php

namespace ItsmeLaravel\Itsme\Console\Commands;

use Illuminate\Console\Command;
use ItsmeLaravel\Itsme\Services\OpenIdDiscovery;
use ItsmeLaravel\Itsme\Exceptions\ItsmeException;

class TestItsmeConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'itsme:test-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Itsme configuration and connectivity';

    /**
     * Execute the console command.
     */
    public function handle(OpenIdDiscovery $discovery): int
    {
        $this->info('Testing Itsme Configuration...');
        $this->newLine();

        // Check configuration
        $this->checkConfiguration();

        // Test discovery
        $this->testDiscovery($discovery);

        $this->newLine();
        $this->info('✅ Configuration test completed!');

        return Command::SUCCESS;
    }

    /**
     * Check basic configuration.
     */
    protected function checkConfiguration(): void
    {
        $this->info('Checking configuration...');

        $checks = [
            'Client ID' => config('itsme.client_id'),
            'Client Secret' => config('itsme.client_secret') ? '***' : null,
            'Redirect URI' => config('itsme.redirect'),
            'Environment' => config('itsme.environment'),
            'Use PKCE' => config('itsme.use_pkce') ? 'Yes' : 'No',
            'Verify Token' => config('itsme.verify_token_signature') ? 'Yes' : 'No',
        ];

        foreach ($checks as $key => $value) {
            if ($value === null || $value === '') {
                $this->error("  ❌ {$key}: Not set");
            } else {
                $this->line("  ✅ {$key}: {$value}");
            }
        }

        $this->newLine();
    }

    /**
     * Test OpenID Connect discovery.
     */
    protected function testDiscovery(OpenIdDiscovery $discovery): void
    {
        $this->info('Testing OpenID Connect discovery...');

        try {
            $config = $discovery->getConfiguration();

            $this->line('  ✅ Discovery successful');
            $this->line('  ✅ Authorization endpoint: ' . ($config['authorization_endpoint'] ?? 'N/A'));
            $this->line('  ✅ Token endpoint: ' . ($config['token_endpoint'] ?? 'N/A'));
            $this->line('  ✅ UserInfo endpoint: ' . ($config['userinfo_endpoint'] ?? 'N/A'));

        } catch (ItsmeException $e) {
            $this->error('  ❌ Discovery failed: ' . $e->getMessage());
            $this->warn('  Make sure your discovery URL is correct and accessible.');
        }
    }
}

