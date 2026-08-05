<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeRepoCommand extends Command
{
    protected $signature = 'make:repo {name} {--migration}';

    protected $description = 'Create model + repository extending BaseRepository';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $modelPath = app_path("Models/{$name}.php");
        $repoPath = app_path("Repositories/{$name}Repository.php");

        if (! File::exists($modelPath)) {
            $this->call('make:model', ['name' => $name]);
        }

        File::ensureDirectoryExists(app_path('Repositories'));

        $extends = class_exists(\Jsdecena\Baserepo\BaseRepository::class)
            ? '\\Jsdecena\\Baserepo\\BaseRepository'
            : 'BaseRepository';

        $contents = <<<PHP
<?php

namespace App\Repositories;

use App\Models\\{$name};

class {$name}Repository extends {$extends}
{
    public function __construct({$name} \$model)
    {
        parent::__construct(\$model);
    }
}

PHP;

        File::put($repoPath, $contents);
        $this->info("Created: {$repoPath}");

        if ($this->option('migration')) {
            $table = Str::snake(Str::pluralStudly($name));
            $this->call('make:migration', [
                'name' => "create_{$table}_table",
                '--create' => $table,
            ]);
        }

        return self::SUCCESS;
    }
}
