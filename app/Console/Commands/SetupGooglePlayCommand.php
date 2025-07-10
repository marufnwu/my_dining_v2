<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SetupGooglePlayCommand extends Command
{
    protected $signature = 'setup:google-play
        {--credentials-file= : Path to the Google Play credentials JSON file}
        {--package-name= : Your Android app package name}';

    protected $description = 'Set up Google Play integration credentials';

    public function handle()
    {
        $this->info('Setting up Google Play integration...');

        // Check required PHP extensions
        $this->checkRequirements();

        // Get credentials file path
        $credentialsFile = $this->option('credentials-file');
        if (!$credentialsFile) {
            $credentialsFile = $this->ask('Please provide the path to your Google Play credentials JSON file:');
        }

        // Get package name
        $packageName = $this->option('package-name');
        if (!$packageName) {
            $packageName = $this->ask('Please provide your Android app package name (e.g. com.example.app):');
        }

        // Validate package name format
        if (!preg_match('/^[a-z][a-z0-9_]*(\.[a-z0-9_]+)+[0-9a-z_]$/i', $packageName)) {
            $this->error('Invalid package name format! Should be like com.example.app');
            return 1;
        }

        // Validate file exists
        if (!file_exists($credentialsFile)) {
            $this->error('Credentials file not found!');
            return 1;
        }

        // Validate JSON format and required fields
        if (!$this->validateCredentials($credentialsFile)) {
            return 1;
        }

        // Copy credentials to storage
        try {
            $credentials = json_decode(file_get_contents($credentialsFile), true);
            Storage::put('google-play-credentials.json', json_encode($credentials, JSON_PRETTY_PRINT));
            $this->info('Google Play credentials have been configured successfully!');

            // Update environment variable for package name
            $this->updateEnvFile('GOOGLE_PLAY_PACKAGE_NAME', $packageName);

            // Create or update payment method
            $this->call('db:seed', [
                '--class' => 'PaymentMethodSeeder',
                '--force' => true
            ]);

            // Verify the setup
            if ($this->verifySetup($packageName)) {
                $this->info('Google Play integration has been fully configured and verified!');
                $this->info('Webhook URL: ' . config('app.url') . '/api/v1/payments/google-play/webhook');
                return 0;
            } else {
                $this->error('Setup verification failed. Please check the configuration and try again.');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Failed to save credentials: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Check required PHP extensions and configurations
     */
    protected function checkRequirements(): void
    {
        $requirements = [
            'openssl' => 'OpenSSL extension is required for Google Play API authentication',
            'json' => 'JSON extension is required for API communication',
            'curl' => 'cURL extension is required for API requests',
        ];

        $failed = false;
        foreach ($requirements as $ext => $message) {
            if (!extension_loaded($ext)) {
                $this->error($message);
                $failed = true;
            }
        }

        if ($failed) {
            throw new \RuntimeException('Please install required PHP extensions.');
        }
    }

    /**
     * Validate credentials file format and required fields
     */
    protected function validateCredentials(string $file): bool
    {
        $credentials = json_decode(file_get_contents($file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON format in credentials file!');
            return false;
        }

        $requiredFields = [
            'type' => 'service_account',
            'project_id' => 'Project ID',
            'private_key_id' => 'Private key ID',
            'private_key' => 'Private key',
            'client_email' => 'Client email',
            'auth_uri' => 'Auth URI',
            'token_uri' => 'Token URI',
            'auth_provider_x509_cert_url' => 'Auth provider certificate URL',
            'client_x509_cert_url' => 'Client certificate URL',
        ];

        foreach ($requiredFields as $field => $label) {
            if (!isset($credentials[$field])) {
                $this->error("Missing required field: {$label}");
                return false;
            }
        }

        // Validate service account type
        if ($credentials['type'] !== 'service_account') {
            $this->error('Invalid credential type. Must be a service account.');
            return false;
        }

        return true;
    }

    /**
     * Verify the setup by attempting to initialize the Google Play client
     */
    protected function verifySetup(string $packageName): bool
    {
        try {
            $client = new \Google\Client();
            $client->setAuthConfig(storage_path('app/google-play-credentials.json'));
            $client->addScope('https://www.googleapis.com/auth/androidpublisher');

            $androidPublisher = new \Google\Service\AndroidPublisher($client);

            // Try to get application details (this will verify our authentication works)
            $androidPublisher->edits->insert($packageName);

            return true;
        } catch (\Exception $e) {
            $this->error('Verification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update or add an environment variable
     */
    protected function updateEnvFile(string $key, string $value): void
    {
        $path = base_path('.env');
        $content = file_get_contents($path);

        if (str_contains($content, $key . '=')) {
            $content = preg_replace(
                "/^{$key}=.*/m",
                "{$key}=\"{$value}\"",
                $content
            );
        } else {
            $content .= "\n{$key}=\"{$value}\"";
        }

        file_put_contents($path, $content);
    }
}
