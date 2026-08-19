<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;

class ExtendedTranslationLoader extends FileLoader
{
    protected string $storageLangPath;

    public function __construct(Filesystem $files, array $paths, string $storageLangPath)
    {
        parent::__construct($files, $paths);
        $this->storageLangPath = rtrim($storageLangPath, \DIRECTORY_SEPARATOR);
    }

    public function load($locale, $group, $namespace = null): array
    {
        $base = parent::load($locale, $group, $namespace);

        if ($namespace !== null && $namespace !== '*') {
            return $base;
        }

        if (! $this->files->exists($this->storageLangPath)) {
            return $base;
        }

        if ($group === '*') {
            $jsonGlobal = $this->storageLangPath.\DIRECTORY_SEPARATOR."{$locale}.json";
            if ($this->files->exists($jsonGlobal)) {
                $decoded = json_decode($this->files->get($jsonGlobal), true);
                if (\is_array($decoded)) {
                    $base = array_replace($base, $decoded);
                }
            }

            return $base;
        }

        $groupJson = $this->storageLangPath
            .\DIRECTORY_SEPARATOR
            .$locale
            .\DIRECTORY_SEPARATOR
            ."{$group}.json";

        if ($this->files->exists($groupJson)) {
            $decoded = json_decode($this->files->get($groupJson), true);
            if (\is_array($decoded)) {
                $base = array_replace($base, $decoded);
            }
        }

        return $base;
    }

    protected function loadJsonPaths($locale): array
    {
        $base = parent::loadJsonPaths($locale);

        if (! $this->files->exists($this->storageLangPath)) {
            return $base;
        }

        $storageJson = $this->storageLangPath.\DIRECTORY_SEPARATOR."{$locale}.json";
        if ($this->files->exists($storageJson)) {
            $decoded = json_decode($this->files->get($storageJson), true);
            if (\is_array($decoded)) {
                $base = array_merge($base, $decoded);
            }
        }

        return $base;
    }
}
