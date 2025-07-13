<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Config\FeatureConfig;

class FeatureSeeder extends Seeder
{
    public function run()
    {
        FeatureConfig::initializeDefaultFeatures();
    }
}
