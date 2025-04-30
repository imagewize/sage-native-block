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
 * To create a block with a custom name (e.g., 'cool-block'):
 * $ wp acorn sage-native-block:add-setup cool-block
 *   (This will create a block named 'vendor/cool-block')
 *
 * To create a block with a specific vendor prefix (e.g., 'imagewize/my-cool-block'):
 * $ wp acorn sage-native-block:add-setup imagewize/my-cool-block
 *
 * This will create block files in resources/js/blocks/<block-name-without-vendor>/
 * The block.json 'name' will always include a vendor prefix (e.g., 'vendor/cool-block' or 'imagewize/my-cool-block').
 * The block.json 'textdomain' will be set to the vendor ('vendor' or 'imagewize').
 * The default CSS class will be 'wp-block-vendor-cool-block' or 'wp-block-imagewize-my-cool-block'.
 *
 * To skip confirmation prompt:
 * $ wp acorn sage-native-block:add-setup --force
 *
 * You can combine parameters:
 * $ wp acorn sage-native-block:add-setup imagewize/my-cool-block --force
 */
class SageNativeBlockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sage-native-block:add-setup 
                            {blockName? : The name of the block (e.g., "my-block" or "vendor/my-block"), defaults to example-block}
                            {--force : Force the operation to run without confirmation}
                            {--vendor= : Override the default vendor prefix for the block}';

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
        $blockNameInput = $this->argument('blockName') ?: 'example-block';
        
        // Get vendor prefix from option or config
        $vendorPrefix = $this->option('vendor') ?: config('sage-native-block.default_vendor_prefix');

        // Ensure block name always has a vendor prefix
        if (!str_contains($blockNameInput, '/')) {
            $fullBlockName = $vendorPrefix . '/' . $blockNameInput;
            $this->info("Using vendor prefix: '{$vendorPrefix}' for block name: '{$fullBlockName}'");
        } else {
            $fullBlockName = $blockNameInput;
        }

        $setupPath = $this->resolvePath($rootsFiles, 'app/setup.php');

        if (! $this->files->exists($setupPath)) {
            $this->error("Error: Theme setup file not found at {$setupPath}.");

            return static::FAILURE;
        }

        $currentContent = $this->files->get($setupPath);

        // Check if the code is already present
        if (str_contains($currentContent, 'register_block_type($block_json_path);')) {
            $this->info("Block registration code already exists in {$setupPath}.");
        }

        // Confirm action unless forced
        if (! $this->option('force') && ! $this->confirm("This will modify {$setupPath} (if needed) and copy block templates for '{$fullBlockName}'. Do you wish to continue?")) {
            $this->line('Operation cancelled.');

            return static::SUCCESS;
        }

        // Create a backup of the setup file only if we are modifying it
        $backupPath = null;
        if (!str_contains($currentContent, 'register_block_type($block_json_path);')) {
            $backupPath = $setupPath.'.backup-'.date('Y-m-d-His');
            $this->files->copy($setupPath, $backupPath);
            $this->line("Created backup at {$backupPath}");
        }

        // Extract the base name for directory structure
        $directoryBlockName = $this->getDirectoryBlockName($fullBlockName);
        $codeToAdd = $this->getBlockRegistrationCode();

        try {
            // Append the code to the file only if it's not already there
            if (!str_contains($currentContent, 'register_block_type($block_json_path);')) {
                if ($this->files->append($setupPath, $codeToAdd)) {
                    $this->info("Successfully added block registration code to {$setupPath}.");
                } else {
                    $this->error("Failed to write to {$setupPath}. Reverting to backup...");
                    if ($backupPath) {
                        $this->files->copy($backupPath, $setupPath);
                    }
                    return static::FAILURE;
                }
            }

            // Copy block template files using the full name for replacements and directory name for path
            if ($this->copyBlockStubs($rootsFiles, $fullBlockName, $directoryBlockName)) {
                $this->info("Successfully copied block template files to theme resources directory.");
            } else {
                 // If copying fails, potentially revert setup.php if it was modified in this run
                 if ($backupPath && $this->files->exists($backupPath)) {
                    $this->warn('Restoring setup.php from backup due to error during stub copying...');
                    $this->files->copy($backupPath, $setupPath);
                    $this->info('Backup restored.');
                }
                return static::FAILURE;
            }
            
            // Update JS file to include block assets
            $this->updateJsFile($rootsFiles);

            return static::SUCCESS;

        } catch (\Exception $e) {
            $this->error('An error occurred: '.$e->getMessage());

            if ($backupPath && $this->files->exists($backupPath)) {
                $this->warn('Restoring setup.php from backup...');
                $this->files->copy($backupPath, $setupPath);
                $this->info('Backup restored.');
            }

            return static::FAILURE;
        }
    }

    /**
     * Get the base block name for directory structure.
     * Example: 'vendor/my-block' -> 'my-block', 'my-block' -> 'my-block'
     */
    protected function getDirectoryBlockName(string $fullBlockName): string
    {
        if (str_contains($fullBlockName, '/')) {
            $parts = explode('/', $fullBlockName);
            return end($parts);
        }
        return $fullBlockName;
    }

    /**
     * Get the generic block registration code to be added.
     */
    protected function getBlockRegistrationCode(): string
    {
        // Get the block directory path from config
        $blockDirPath = config('sage-native-block.block_directory', 'resources/js/blocks');
        
        // Convert relative path to show in PHP code
        $relativePath = str_replace('\'', '\\\'', $blockDirPath);
        
        return <<<PHP

/**
 * Register block types using block.json metadata from the theme's blocks directory.
 * This function will scan the '{$relativePath}' directory for block.json files.
 */
add_action('init', function () {
    \$blocks_dir = get_template_directory() . '/{$relativePath}';
    if (!is_dir(\$blocks_dir)) {
        return;
    }

    \$block_folders = scandir(\$blocks_dir);

    foreach (\$block_folders as \$folder) {
        if (\$folder === '.' || \$folder === '..') {
            continue;
        }

        \$block_json_path = \$blocks_dir . '/' . \$folder . '/block.json';

        if (file_exists(\$block_json_path)) {
            register_block_type(\$block_json_path);
        }
    }
}, 10);

PHP;
    }

    /**
     * Copy block stub files to the theme's resources directory.
     * Uses directoryBlockName for the target path and fullBlockName for replacements.
     */
    protected function copyBlockStubs(RootsFilesystem $rootsFiles, string $fullBlockName, string $directoryBlockName): bool
    {
        try {
            // Source stub directory
            $stubsDir = dirname(__DIR__, 2).'/stubs/block';

            // Get block directory path from config
            $blockDirPath = config('sage-native-block.block_directory', 'resources/js/blocks');
            
            // Target directory in the theme using the directoryBlockName
            $targetDir = $this->resolvePath($rootsFiles, "{$blockDirPath}/{$directoryBlockName}");

            // Verify the target path is within the theme
            $this->line("Target directory will be: {$targetDir}");

            // Create target directory if it doesn't exist
            if (! $this->files->isDirectory($targetDir)) {
                $this->files->makeDirectory($targetDir, 0755, true);
                $this->line("Created directory: {$targetDir}");
            } else {
                 $this->warn("Target directory already exists: {$targetDir}. Files will be overwritten.");
            }

            // Files to copy with their template config keys
            $files = [
                'block.json' => 'block_json',
                'index.js' => 'index_js',
                'editor.jsx' => 'editor_jsx',
                'save.jsx' => 'save_jsx',
                'editor.css' => 'editor_css',
                'style.css' => 'style_css',
                'view.js' => 'view_js',
            ];

            // Copy each file
            foreach ($files as $file => $configKey) {
                // Check if a custom template is defined in config
                $customTemplatePath = config("sage-native-block.templates.{$configKey}");
                
                // Use custom template if defined and exists, otherwise use default stub
                $source = $customTemplatePath && file_exists($customTemplatePath) 
                    ? $customTemplatePath 
                    : "{$stubsDir}/{$file}";
                
                $target = "{$targetDir}/{$file}";

                if ($this->files->exists($source)) {
                    $content = $this->files->get($source);
                    
                    // Use the full block name for replacements
                    if ($file === 'block.json') {
                        $content = $this->replaceBlockName($content, $fullBlockName);
                    }
                    // Replace CSS class references in CSS files
                    elseif ($file === 'style.css' || $file === 'editor.css') {
                        $content = $this->replaceCssClassName($content, $fullBlockName);
                    }
                    // Replace CSS class references in view.js
                    elseif ($file === 'view.js') {
                        $content = $this->replaceJsClassName($content, $fullBlockName);
                    }
                    
                    $this->files->put($target, $content);
                    $this->line("Copied and processed: {$file}" . ($customTemplatePath ? " (using custom template)" : ""));
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
     * Replace placeholders in block.json content with the provided full block name.
     */
    protected function replaceBlockName(string $content, string $fullBlockName): string
    {
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->warn('Could not parse block.json stub, using it as is.');
            return $content;
        }

        // Parse the full block name (guaranteed to have a vendor by handle())
        $vendor = 'vendor'; // Default if parsing fails unexpectedly
        $actualBlockName = $fullBlockName;
        if (str_contains($fullBlockName, '/')) {
            $parts = explode('/', $fullBlockName, 2);
            $vendor = $parts[0];
            $actualBlockName = $parts[1];
        } else {
            // This case should ideally not be reached due to logic in handle()
             $this->warn("Block name '{$fullBlockName}' unexpectedly lacks a vendor prefix during replacement. Defaulting vendor to 'vendor'.");
             $actualBlockName = $fullBlockName; // Use the full name as the actual name
        }

        // Update the name field
        $data['name'] = $fullBlockName;

        // Update the title field based on the actual block name part
        if (isset($data['title'])) {
            $formattedName = str_replace('-', ' ', $actualBlockName);
            $formattedName = ucwords($formattedName);
            $data['title'] = $formattedName;
        }

        // Update textdomain to match the determined vendor
        if (isset($data['textdomain'])) { // Check if textdomain key exists in stub
             $data['textdomain'] = $vendor;
        }

        // Update the className default
        if (isset($data['attributes']['className']['default'])) {
            $className = 'wp-block-' . str_replace('/', '-', $fullBlockName);
            $data['attributes']['className']['default'] = $className;
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Replace example CSS class name with the generated class name based on the full block name.
     */
    protected function replaceCssClassName(string $content, string $fullBlockName): string
    {
        // Generate class name like wp-block-vendor-block-name or wp-block-block-name
        $className = 'wp-block-' . str_replace('/', '-', $fullBlockName);
        
        // Replace the placeholder class. No need to escape the replacement string.
        return preg_replace('/\.wp-block-vendor-example-block/', ".{$className}", $content);
    }
    
    /**
     * Replace example CSS class name with the generated class name in JS files.
     */
    protected function replaceJsClassName(string $content, string $fullBlockName): string
    {
        $className = 'wp-block-' . str_replace('/', '-', $fullBlockName);
        // Replace the placeholder class literal.
        return str_replace('.wp-block-vendor-example-block', ".{$className}", $content);
    }

    /**
     * Update editor.js to import block index.js files for the editor.
     */
    protected function updateJsFile(RootsFilesystem $rootsFiles): void
    {
        $jsPath = $this->resolvePath($rootsFiles, 'resources/js/editor.js');

        if (! $this->files->exists($jsPath)) {
            $this->warn("Editor JS file not found at {$jsPath}. Creating it...");
            // Use NOWDOC for initial content to avoid issues with special characters
            $initialContent = <<<'JS'
import domReady from '@wordpress/dom-ready';

/**
 * Import editor blocks
 */
import.meta.glob('./blocks/**/index.js', { eager: true });

domReady(() => {
  // Initialize editor scripts here
});
JS; // Ensure this is at the start of the line with no preceding whitespace
            $this->files->put($jsPath, $initialContent);
            $this->info("Created {$jsPath} with block import code.");
        } else {
            $jsContent = $this->files->get($jsPath);

            $globPattern = './blocks/**/index.js';
            // Use regex for a more robust check of the import line existence
            if (! preg_match('/import\.meta\.glob\(\s*[\'"]' . preg_quote($globPattern, '/') . '[\'"]/', $jsContent)) {
                // Use NOWDOC for the code to add
                $jsToAdd = <<<'JS'

/**
 * Import editor blocks
 */
import.meta.glob('./blocks/**/index.js', { eager: true });

JS; // Ensure this is at the start of the line with no preceding whitespace
                // Prepend the import code to the existing content
                $this->files->put($jsPath, $jsToAdd . $jsContent);
                $this->info("Added block JS import code to {$jsPath}");
            } else {
                $this->info("Block JS import code already exists in {$jsPath}");
            }
        }
    }
}
