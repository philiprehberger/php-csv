<?php

declare(strict_types=1);

namespace PhilipRehberger\Csv;

use PhilipRehberger\Csv\Exceptions\CsvReadException;

/**
 * Static entry point for reading and writing CSV files.
 */
class Csv
{
    /**
     * Create a new CSV reader for a file path.
     *
     * @throws CsvReadException
     */
    public static function read(string $path): CsvReader
    {
        if (! file_exists($path)) {
            throw new CsvReadException("File not found: {$path}");
        }

        if (! is_readable($path)) {
            throw new CsvReadException("File is not readable: {$path}");
        }

        $stream = fopen($path, 'r');

        if ($stream === false) {
            throw new CsvReadException("Failed to open file: {$path}");
        }

        return new CsvReader($stream);
    }

    /**
     * Create a new CSV reader from a string.
     */
    public static function readString(string $content): CsvReader
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new CsvReadException('Failed to create temporary stream');
        }

        fwrite($stream, $content);
        rewind($stream);

        return new CsvReader($stream);
    }

    /**
     * Create a new CSV writer for a file path.
     */
    public static function write(string $path): CsvWriter
    {
        return new CsvWriter($path);
    }
}
