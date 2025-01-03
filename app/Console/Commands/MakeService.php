<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {name : The name of the service class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service class';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $path = app_path("Services/{$name}.php");

        if (File::exists($path)) {
            $this->error("Service {$name} already exists!");
            return 1;
        }

        // Generate the service file content
        $content = <<<EOT
<?php

namespace App\Services;

class {$name}
{
    // Add your service methods here
}
EOT;

        // Create the file and write the content
        File::ensureDirectoryExists(app_path('Services'));
        File::put($path, $content);

        $this->info("Service {$name} created successfully.");
        return 0;
    }
}
