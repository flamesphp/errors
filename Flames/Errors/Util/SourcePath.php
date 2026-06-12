<?php

declare(strict_types=1);

namespace Flames\Errors\Util;

/**
 * Resolves virtual source paths (e.g. subset stream wrappers) to real files on disk.
 */
final class SourcePath
{
    public static function resolve(string $file): string
    {
        if (str_starts_with($file, 'flames://')) {
            return substr($file, 9);
        }

        return $file;
    }
}
