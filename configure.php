#!/usr/bin/env php
<?php

function ask(string $question, string $default = ''): string
{
    $answer = readline($question.($default ? " ({$default})" : null).': ');

    if (! $answer) {
        return $default;
    }

    return $answer;
}

function confirm(string $question, bool $default = false): bool
{
    $answer = ask($question.' ('.($default ? 'Y/n' : 'y/N').')');

    if (! $answer) {
        return $default;
    }

    return strtolower($answer) === 'y';
}

function writeln(string $line): void
{
    echo $line.PHP_EOL;
}

function run(string $command): string
{
    return trim((string) shell_exec($command));
}

function str_after(string $subject, string $search): string
{
    $pos = strrpos($subject, $search);

    if ($pos === false) {
        return $subject;
    }

    return substr($subject, $pos + strlen($search));
}

function slugify(string $subject): string
{
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $subject), '-'));
}

function title_case(string $subject): string
{
    return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $subject)));
}

function replace_in_file(string $file, array $replacements): void
{
    $contents = file_get_contents($file);

    file_put_contents(
        $file,
        str_replace(
            array_keys($replacements),
            array_values($replacements),
            $contents
        )
    );
}

function determineSeparator(string $path): string
{
    return str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function replaceForWindows(): array
{
    return preg_split('/\\r\\n|\\r|\\n/', run('dir /S /B * | findstr /v /i .git\ | findstr /v /i vendor | findstr /v /i '.basename(__FILE__).' | findstr /r /i /M /F:/ ":author :vendor :package VendorName vendor_name vendor_slug author@domain.com"'));
}

function replaceForAllOtherOSes(): array
{
    return explode(PHP_EOL, run('grep -E -r -l -i ":author|:vendor|:package|VendorName|vendor_name|vendor_slug|author@domain.com" --exclude-dir=vendor ./* ./.github/* | grep -v '.basename(__FILE__)));
}

$gitName = run('git config user.name');
$authorName = ask('Author name', $gitName);

$gitEmail = run('git config user.email');
$authorEmail = ask('Author email', $gitEmail);
$authorUsername = ask('Author username', 'developer@roots.io');

$vendorName = ask('Vendor name', 'Roots');
$vendorUsername = ask('Vendor username', 'roots');
$vendorSlug = slugify($vendorUsername);

$vendorNamespace = str_replace(' ', '', str_replace('-', '', ucwords($vendorName)));
$vendorNamespace = ask('Vendor namespace', $vendorNamespace);

$currentDirectory = getcwd();
$folderName = basename($currentDirectory);

$packageName = ask('Package name', $folderName);
$packageSlug = slugify($packageName);

$className = title_case($packageName);
$className = ask('Class name', $className);
$variableName = lcfirst($className);
$description = ask('Package description', "This is my package {$packageSlug}");

writeln('------');
writeln("Author     : {$authorName} ({$authorUsername}, {$authorEmail})");
writeln("Vendor     : {$vendorName} ({$vendorSlug})");
writeln("Package    : {$packageSlug} <{$description}>");
writeln("Namespace  : {$vendorNamespace}\\{$className}");
writeln("Class name : {$className}");
writeln('------');

writeln('This script will replace the above values in all relevant files in the project directory.');

if (! confirm('Modify files?', true)) {
    exit(1);
}

$files = (str_starts_with(strtoupper(PHP_OS), 'WIN') ? replaceForWindows() : replaceForAllOtherOSes());

foreach ($files as $file) {
    replace_in_file($file, [
        ':author_name' => $authorName,
        ':author_username' => $authorUsername,
        ':author_email' => $authorEmail,
        ':vendor_name' => $vendorNamespace,
        ':vendor_slug' => $vendorSlug,
        'VendorName' => $vendorNamespace,
        ':package_name' => $packageName,
        ':package_slug' => $packageSlug,
        'Example' => $className,
        'ExamplePackage' => $className,
        'example' => $packageSlug,
        ':package_description' => $description,
        ':class_name' => $className,
    ]);

    match (true) {
        str_contains($file, determineSeparator('src/Example.php')) => rename($file, determineSeparator('./src/'.$className.'.php')),
        str_contains($file, determineSeparator('src/Providers/ExampleServiceProvider.php')) => rename($file, determineSeparator('./src/providers/'.$className.'ServiceProvider.php')),
        str_contains($file, determineSeparator('src/Console/ExampleCommand.php')) => rename($file, determineSeparator('./src/console/'.$className.'.php')),
        str_contains($file, determineSeparator('src/Facades/Example.php')) => rename($file, determineSeparator('./src/Facades/'.$className.'.php')),
        str_contains($file, determineSeparator('resources/views/example.blade.php')) => rename($file, determineSeparator('./resources/views/'.$packageSlug.'.blade.php')),
        default => [],
    };
}

rename(determineSeparator('config/example.php'), determineSeparator('./config/'.$packageSlug.'.php'));

confirm('Execute `composer install`?') && run('composer install');

confirm('Let this script delete itself?', true) && unlink(__FILE__);
