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

    /**
     * Create a new TSV reader for a file path.
     *
     * @throws CsvReadException
     */
    public static function readTsv(string $path): CsvReader
    {
        return self::read($path)->delimiter("\t");
    }

    /**
     * Create a new TSV writer.
     */
    public static function writeTsv(): CsvWriter
    {
        return (new CsvWriter(''))->delimiter("\t");
    }

    /**
     * Create a new PSV (pipe-separated) reader for a file path.
     *
     * @throws CsvReadException
     */
    public static function readPsv(string $path): CsvReader
    {
        return self::read($path)->delimiter('|');
    }

    /**
     * Create a new PSV (pipe-separated) writer.
     */
    public static function writePsv(): CsvWriter
    {
        return (new CsvWriter(''))->delimiter('|');
    }

    /**
     * Create a new streaming CSV writer for a file path.
     *
     * Unlike `write()`, the streaming writer flushes rows directly to disk
     * without buffering, making it suitable for very large files.
     */
    public static function streamWrite(string $path, string $delimiter = ','): StreamingWriter
    {
        return new StreamingWriter($path, $delimiter);
    }
}
