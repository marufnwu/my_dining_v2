<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PlanBuilderService;
use App\Config\FeatureConfig;

class CreatePlanCommand extends Command
{
    protected $signature = 'plan:create {name} {keyword} {--description=} {--features=*}';
    protected $description = 'Create a new subscription plan';

    public function handle(PlanBuilderService $planBuilder)
    {
        $name = $this->argument('name');
        $keyword = $this->argument('keyword');
        $description = $this->option('description') ?? "Description for {$name}";
        $features = $this->option('features');

        // Show available features if none provided
        if (empty($features)) {
            $this->showAvailableFeatures();
            return;
        }

        // Parse feature limits
        $featureLimits = $this->parseFeatureLimits($features);

        // Validate feature limits
        $validation = $planBuilder->validateFeatureLimits($featureLimits);
        if (!$validation->isSuccess()) {
            $this->error('Validation failed:');
            foreach ($validation->getData() as $error) {
                $this->error("- {$error}");
            }
            return;
        }

        // Create the plan
        $result = $planBuilder->createPlan([
            'name' => $name,
            'keyword' => $keyword,
            'description' => $description,
            'is_active' => true
        ], $featureLimits);

        if ($result->isSuccess()) {
            $this->info("Plan '{$name}' created successfully!");

            // Show created plan features
            $plan = $result->getData();
            $this->info("\nPlan features:");
            $features = $planBuilder->getPlanFeaturesWithUsage($plan);
            foreach ($features as $feature) {
                $this->line("- {$feature['name']}: {$feature['usage_limit']} (reset: {$feature['reset_period']})");
            }
        } else {
            $this->error("Failed to create plan: {$result->message}");
        }
    }

    private function showAvailableFeatures()
    {
        $this->info('Available features:');
        $features = FeatureConfig::getFeatureDefinitions();

        foreach ($features as $name => $definition) {
            $this->line("- {$name} ({$definition['description']}) - Category: {$definition['category']}");
        }

        $this->info("\nUsage example:");
        $this->line('php artisan plan:create "Premium Plan" premium --features="Member Limit:20" --features="Report Generate:10"');
    }

    private function parseFeatureLimits(array $features): array
    {
        $limits = [];

        foreach ($features as $feature) {
            [$name, $limit] = explode(':', $feature);
            $limits[trim($name)] = (int) trim($limit);
        }

        return $limits;
    }
}
