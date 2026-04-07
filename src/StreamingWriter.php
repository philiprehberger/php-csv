<?php

declare(strict_types=1);

namespace PhilipRehberger\Csv;

use PhilipRehberger\Csv\Exceptions\CsvWriteException;

/**
 * Streaming CSV writer that writes rows directly without buffering.
 */
class StreamingWriter
{
    /** @var resource|null */
    private mixed $handle;

    private string $enclosure = '"';

    private string $escape = '\\';

    private bool $headerWritten = false;

    /**
     * Create a new streaming writer instance.
     *
     * @throws CsvWriteException
     */
    public function __construct(
        string $path,
        private readonly string $delimiter = ',',
    ) {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            throw new CsvWriteException("Directory does not exist: {$directory}");
        }

        $handle = fopen($path, 'w');

        if ($handle === false) {
            throw new CsvWriteException("Failed to open file for writing: {$path}");
        }

        $this->handle = $handle;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Set the field enclosure character.
     */
    public function enclosure(string $enclosure): self
    {
        $this->enclosure = $enclosure;

        return $this;
    }

    /**
     * Set the field escape character.
     */
    public function escape(string $escape): self
    {
        $this->escape = $escape;

        return $this;
    }

    /**
     * Write the header row.
     *
     * @param  array<int, string>  $headers
     *
     * @throws CsvWriteException
     */
    public function writeHeader(array $headers): void
    {
        $this->ensureOpen();
        fputcsv($this->handle, $headers, $this->delimiter, $this->enclosure, $this->escape);
        $this->headerWritten = true;
    }

    /**
     * Write a single data row.
     *
     * @param  array<int|string, mixed>  $row
     *
     * @throws CsvWriteException
     */
    public function writeRow(array $row): void
    {
        $this->ensureOpen();
        fputcsv($this->handle, $row, $this->delimiter, $this->enclosure, $this->escape);
    }

    /**
     * Write multiple data rows.
     *
     * @param  array<int, array<int|string, mixed>>  $rows
     *
     * @throws CsvWriteException
     */
    public function writeRows(array $rows): void
    {
        foreach ($rows as $row) {
            $this->writeRow($row);
        }
    }

    /**
     * Whether the header row has been written.
     */
    public function isHeaderWritten(): bool
    {
        return $this->headerWritten;
    }

    /**
     * Close the underlying file handle.
     */
    public function close(): void
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }

        $this->handle = null;
    }

    /**
     * Ensure the file handle is still open.
     *
     * @throws CsvWriteException
     */
    private function ensureOpen(): void
    {
        if (! is_resource($this->handle)) {
            throw new CsvWriteException('Writer has been closed');
        }
    }
}
