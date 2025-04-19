<?php

namespace Imagewize\SageNativeBlockPackage\Console;

use Illuminate\Filesystem\Filesystem;
use Roots\Acorn\Console\Commands\Command;
use Roots\Acorn\Filesystem\Filesystem as RootsFilesystem;

/**
 * Command to add native block registration code to the Sage theme setup file and create block files.
 *
 * Run this command from your WordPress site root or theme directory:
 * $ wp acorn sage-native-block:add-setup
 *
 * To create a block with a custom name:
 * $ wp acorn sage-native-block:add-setup my-cool-block
 * 
 * This will create a block in resources/js/blocks/my-cool-block/ and update all references.
 *
 * To skip confirmation prompt:
 * $ wp acorn sage-native-block:add-setup --force
 * 
 * You can combine both parameters:
 * $ wp acorn sage-native-block:add-setup my-cool-block --force
 */
class SageNativeBlockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sage-native-block:add-setup 
                            {blockName? : The name of the block to create, defaults to example-block if not provided}
                            {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add native block registration code to app/setup.php.';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }
    
    /**
     * Resolve the theme path using RootsFilesystem or fallback to WordPress methods
     *
     * @param RootsFilesystem $rootsFiles
     * @param string $relativePath
     * @return string
     */
    protected function resolvePath(RootsFilesystem $rootsFiles, string $relativePath = ''): string
    {
        // Try Acorn-specific method if available
        try {
            if (method_exists($rootsFiles, 'path')) {
                return $rootsFiles->path($relativePath);
            }
        } catch (\Exception $e) {
            // Fallback to WordPress methods
        }
        
        // First fallback: WordPress functions
        if (function_exists('get_template_directory')) {
            $themePath = get_template_directory();
            
            if (!empty($relativePath)) {
                return $themePath . '/' . $relativePath;
            }
            
            return $themePath;
        }
        
        // Last resort: Try to detect the theme path from the command location
        $commandPath = dirname(__DIR__, 5); // Adjust if needed
        $this->warn("Using detected theme path: {$commandPath}");
        
        if (!empty($relativePath)) {
            return $commandPath . '/' . $relativePath;
        }
        
        return $commandPath;
    }

    /**
     * Execute the console command.
     */
    public function handle(RootsFilesystem $rootsFiles): int
    {
        // Get block name from argument or use default
        $blockName = $this->argument('blockName') ?: 'example-block';
        
        $setupPath = $this->resolvePath($rootsFiles, 'app/setup.php');

        if (! $this->files->exists($setupPath)) {
            $this->error("Error: Theme setup file not found at {$setupPath}.");

            return static::FAILURE;
        }

        $currentContent = $this->files->get($setupPath);

        // Check if the code is already present
        if (str_contains($currentContent, 'register_block_type($block_json_path);')) {
            $this->info("Block registration code already exists in {$setupPath}.");

            return static::SUCCESS;
        }

        // Confirm action unless forced
        if (! $this->option('force') && ! $this->confirm("This will modify {$setupPath} and copy block templates. Do you wish to continue?")) {
            $this->line('Operation cancelled.');

            return static::SUCCESS;
        }

        // Create a backup of the file
        $backupPath = $setupPath.'.backup-'.date('Y-m-d-His');
        $this->files->copy($setupPath, $backupPath);
        $this->line("Created backup at {$backupPath}");

        $codeToAdd = $this->getBlockRegistrationCode($blockName);

        try {
            // Append the code to the file
            if ($this->files->append($setupPath, $codeToAdd)) {
                $this->info("Successfully added block registration code to {$setupPath}.");

                // Copy block template files
                if ($this->copyBlockStubs($rootsFiles, $blockName)) {
                    $this->info("Successfully copied block template files to theme resources directory.");
                }
                
                // Update CSS and JS files to include block assets
                $this->updateCssFile($rootsFiles);
                $this->updateJsFile($rootsFiles);

                return static::SUCCESS;
            }

            $this->error("Failed to write to {$setupPath}. Reverting to backup...");
            $this->files->copy($backupPath, $setupPath);

            return static::FAILURE;
        } catch (\Exception $e) {
            $this->error('An error occurred: '.$e->getMessage());

            if ($this->files->exists($backupPath)) {
                $this->warn('Restoring from backup...');
                $this->files->copy($backupPath, $setupPath);
                $this->info('Backup restored.');
            }

            return static::FAILURE;
        }
    }

    /**
     * Get the block registration code to be added.
     */
    protected function getBlockRegistrationCode(string $blockName): string
    {
        return <<<PHP

/**
 * Register block type using block.json metadata.
 */
add_action('init', function () {
    \$block_json_path = get_template_directory().'/resources/js/blocks/{$blockName}/block.json';

    if (file_exists(\$block_json_path)) {
        register_block_type(\$block_json_path);
    }
});

PHP;
    }

    /**
     * Copy block stub files to the theme's resources directory.
     */
    protected function copyBlockStubs(RootsFilesystem $rootsFiles, string $blockName): bool
    {
        try {
            // Source stub directory
            $stubsDir = dirname(__DIR__, 2).'/stubs/block';

            // Target directory in the theme
            $targetDir = $this->resolvePath($rootsFiles, "resources/js/blocks/{$blockName}");

            // Verify the target path is within the theme
            $this->line("Target directory will be: {$targetDir}");

            // Create target directory if it doesn't exist
            if (! $this->files->isDirectory($targetDir)) {
                $this->files->makeDirectory($targetDir, 0755, true);
                $this->line("Created directory: {$targetDir}");
            }

            // Files to copy
            $files = [
                'block.json',
                'index.js',
                'editor.jsx',
                'save.jsx',
                'editor.css',
                'style.css',
                'view.js',
            ];

            // Copy each file
            foreach ($files as $file) {
                $source = "{$stubsDir}/{$file}";
                $target = "{$targetDir}/{$file}";

                if ($this->files->exists($source)) {
                    $content = $this->files->get($source);
                    
                    // Replace block name in block.json
                    if ($file === 'block.json') {
                        $content = $this->replaceBlockName($content, $blockName);
                    }
                    // Replace CSS class references in CSS files
                    elseif ($file === 'style.css' || $file === 'editor.css') {
                        $content = $this->replaceCssBlockName($content, $blockName);
                    }
                    // Replace CSS class references in view.js
                    elseif ($file === 'view.js') {
                        $content = $this->replaceJsBlockName($content, $blockName);
                    }
                    
                    $this->files->put($target, $content);
                    $this->line("Copied and processed: {$file}");
                } else {
                    $this->warn("Source file not found: {$source}");
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->error('Failed to copy block stubs: '.$e->getMessage());

            return false;
        }
    }
    
    /**
     * Replace example-block references with the provided block name in block.json.
     */
    protected function replaceBlockName(string $content, string $blockName): string
    {
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->warn('Could not parse block.json, using it as is.');
            return $content;
        }
        
        // Extract vendor from the name field, default to 'vendor' if not found
        $vendor = 'vendor';
        if (isset($data['name'])) {
            $parts = explode('/', $data['name']);
            if (count($parts) > 1) {
                $vendor = $parts[0];
                // Update the block name while preserving the vendor
                $data['name'] = "{$vendor}/{$blockName}";
            }
        }
        
        // Update the title field based on the block name
        if (isset($data['title'])) {
            $formattedName = str_replace('-', ' ', $blockName);
            $formattedName = ucwords($formattedName);
            $data['title'] = $formattedName;
        }
        
        // Update textdomain to match vendor if it exists
        if (isset($data['textdomain']) && $data['textdomain'] === 'vendor') {
            $data['textdomain'] = $vendor;
        }
        
        // Update the className default using the same vendor
        if (isset($data['attributes']['className']['default'])) {
            $data['attributes']['className']['default'] = "wp-block-{$vendor}-{$blockName}";
        }
        
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Replace example-block references with the provided block name in CSS files.
     */
    protected function replaceCssBlockName(string $content, string $blockName): string
    {
        // Replace .wp-block-vendor-example-block with .wp-block-vendor-{$blockName}
        return preg_replace('/\.wp-block-vendor-example-block/', ".wp-block-vendor-{$blockName}", $content);
    }
    
    /**
     * Replace example-block references with the provided block name in JS files.
     */
    protected function replaceJsBlockName(string $content, string $blockName): string
    {
        // Replace .wp-block-vendor-example-block with .wp-block-vendor-{$blockName}
        // Extract vendor from the block name if available, otherwise use 'vendor'
        $vendor = 'vendor';
        $parts = explode('/', $blockName);
        if (count($parts) > 1) {
            // Assuming the format might become vendor/block-name later
             $vendor = $parts[0];
             $actualBlockName = $parts[1];
        } else {
            $actualBlockName = $blockName; // Use the provided name directly if no vendor prefix
        }
        
        return preg_replace('/\.wp-block-vendor-example-block/', ".wp-block-{$vendor}-{$actualBlockName}", $content);
    }
    
    /**
     * Update app.css to dynamically include CSS from all blocks.
     */
    protected function updateCssFile(RootsFilesystem $rootsFiles): void
    {
        $cssPath = $this->resolvePath($rootsFiles, 'resources/css/app.css');
        
        if (! $this->files->exists($cssPath)) {
            $this->warn("CSS file not found at {$cssPath}. Creating it...");
            $this->files->put($cssPath, '');
        }
        
        $cssContent = $this->files->get($cssPath);
        
        // Check if the CSS source directive is already present
        if (! str_contains($cssContent, '@source "../js/blocks/**/";')) {
            $cssToAdd = "\n\n/* Dynamically include CSS for all blocks in the blocks directory */\n@source \"../js/blocks/**/\";\n";
            $this->files->append($cssPath, $cssToAdd);
            $this->info("Added block CSS source directive to {$cssPath}");
        } else {
            $this->info("Block CSS source directive already exists in {$cssPath}");
        }
    }
    
    /**
     * Update editor.js to import block index.js files for the editor.
     */
    protected function updateJsFile(RootsFilesystem $rootsFiles): void
    {
        $jsPath = $this->resolvePath($rootsFiles, 'resources/js/editor.js'); // Target editor.js
        
        if (! $this->files->exists($jsPath)) {
            $this->warn("Editor JS file not found at {$jsPath}. Creating it...");
            // Create a basic editor.js if it doesn't exist
            $initialContent = <<<'JS'
import domReady from '@wordpress/dom-ready';

domReady(() => {
  // Editor-specific JS logic can go here.
});

JS;
            $this->files->put($jsPath, $initialContent);
        }
        
        $jsContent = $this->files->get($jsPath);
        
        // Check if block import code is already present using import.meta.glob
        // Use a path relative to editor.js
        if (! str_contains($jsContent, 'import.meta.glob(\'./blocks/**/index.js\')')) { 
            $jsToAdd = <<<'JS'

/**
 * Import block index files for editor registration
 */
const blockModules = import.meta.glob('./blocks/**/index.js', { eager: true });

// Log loaded modules (optional)
console.log('Block modules loaded for editor:', blockModules);

JS;
            // Prepend the import to ensure blocks are registered early
            $this->files->put($jsPath, $jsToAdd . "\n" . $jsContent); 
            $this->info("Added block JS import code to {$jsPath}");
        } else {
            $this->info("Block JS import code already exists in {$jsPath}");
        }
    }
}
