<?php

declare(strict_types=1);

namespace PhilipRehberger\Csv;

use PhilipRehberger\Csv\Exceptions\CsvWriteException;

/**
 * Fluent CSV writer with header mapping and BOM support.
 */
class CsvWriter
{
    private string $delimiter = ',';

    private string $enclosure = '"';

    private string $escape = '\\';

    private bool $bom = false;

    /** @var array<int, string> */
    private array $headers = [];

    /** @var array<int, array<int|string, mixed>> */
    private array $rows = [];

    /**
     * Create a new CSV writer instance.
     */
    public function __construct(
        private readonly string $path,
    ) {}

    /**
     * Set the column headers.
     *
     * @param  array<int, string>  $headers
     */
    public function headers(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Add a single row.
     *
     * @param  array<int|string, mixed>  $row
     */
    public function row(array $row): self
    {
        $this->rows[] = $row;

        return $this;
    }

    /**
     * Add multiple rows.
     *
     * @param  array<int, array<int|string, mixed>>  $rows
     */
    public function rows(array $rows): self
    {
        foreach ($rows as $row) {
            $this->rows[] = $row;
        }

        return $this;
    }

    /**
     * Set the field delimiter character.
     */
    public function delimiter(string $delimiter): self
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    /**
     * Enable or disable BOM (Byte Order Mark) for Excel compatibility.
     */
    public function bom(bool $bom): self
    {
        $this->bom = $bom;

        return $this;
    }

    /**
     * Append rows to an existing CSV file without writing headers again.
     *
     * @throws CsvWriteException
     */
    public function appendToFile(string $path): self
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            throw new CsvWriteException("Directory does not exist: {$directory}");
        }

        $stream = fopen($path, 'a');

        if ($stream === false) {
            throw new CsvWriteException("Failed to open file for appending: {$path}");
        }

        try {
            foreach ($this->rows as $row) {
                $values = $this->headers !== []
                    ? $this->extractOrderedValues($row)
                    : array_values($row);

                fputcsv($stream, $values, $this->delimiter, $this->enclosure, $this->escape);
            }
        } finally {
            fclose($stream);
        }

        return $this;
    }

    /**
     * Save the CSV to the configured file path.
     *
     * @throws CsvWriteException
     */
    public function save(): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory)) {
            throw new CsvWriteException("Directory does not exist: {$directory}");
        }

        $stream = fopen($this->path, 'w');

        if ($stream === false) {
            throw new CsvWriteException("Failed to open file for writing: {$this->path}");
        }

        try {
            $this->writeToStream($stream);
        } finally {
            fclose($stream);
        }
    }

    /**
     * Return the CSV as a string.
     */
    public function toString(): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new CsvWriteException('Failed to create temporary stream');
        }

        try {
            $this->writeToStream($stream);
            rewind($stream);

            $content = stream_get_contents($stream);

            if ($content === false) {
                throw new CsvWriteException('Failed to read from temporary stream');
            }

            return $content;
        } finally {
            fclose($stream);
        }
    }

    /**
     * Write the CSV content to a stream.
     *
     * @param  resource  $stream
     */
    private function writeToStream(mixed $stream): void
    {
        if ($this->bom) {
            fwrite($stream, "\xEF\xBB\xBF");
        }

        if ($this->headers !== []) {
            fputcsv($stream, $this->headers, $this->delimiter, $this->enclosure, $this->escape);
        }

        foreach ($this->rows as $row) {
            $values = $this->headers !== []
                ? $this->extractOrderedValues($row)
                : array_values($row);

            fputcsv($stream, $values, $this->delimiter, $this->enclosure, $this->escape);
        }
    }

    /**
     * Extract values from an associative row in header order.
     *
     * @param  array<int|string, mixed>  $row
     * @return array<int, mixed>
     */
    private function extractOrderedValues(array $row): array
    {
        if (array_is_list($row)) {
            return $row;
        }

        $values = [];

        foreach ($this->headers as $header) {
            $values[] = $row[$header] ?? '';
        }

        return $values;
    }
}
