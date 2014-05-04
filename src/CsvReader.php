<?php

declare(strict_types=1);

namespace PhilipRehberger\Csv;

use Generator;
use IteratorAggregate;
use PhilipRehberger\Csv\Exceptions\CsvReadException;
use Traversable;

/**
 * Generator-based CSV reader for memory-efficient processing.
 *
 * @implements IteratorAggregate<int, array<string|int, mixed>>
 */
class CsvReader implements IteratorAggregate
{
    private string $delimiter = ',';

    private string $enclosure = '"';

    private string $escape = '\\';

    private bool $hasHeader = true;

    private bool $skipEmpty = true;

    private bool $castTypes = false;

    /** @var callable|null */
    private $filterCallback = null;

    /** @var callable|null */
    private $mapCallback = null;

    /**
     * Create a new CSV reader instance.
     *
     * @param  resource  $stream
     */
    public function __construct(
        private readonly mixed $stream,
    ) {}

    public function __destruct()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
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
     * Set the field enclosure character.
     */
    public function enclosure(string $enclosure): self
    {
        $this->enclosure = $enclosure;

        return $this;
    }

    /**
     * Set whether the CSV file has a header row.
     */
    public function hasHeader(bool $hasHeader): self
    {
        $this->hasHeader = $hasHeader;

        return $this;
    }

    /**
     * Set whether to skip empty rows.
     */
    public function skipEmpty(bool $skipEmpty): self
    {
        $this->skipEmpty = $skipEmpty;

        return $this;
    }

    /**
     * Enable or disable automatic type casting.
     *
     * When enabled, values are cast: "42" -> int, "3.14" -> float,
     * "true"/"false" -> bool, "" -> null.
     */
    public function castTypes(bool $castTypes): self
    {
        $this->castTypes = $castTypes;

        return $this;
    }

    /**
     * Set a filter callback to exclude rows.
     */
    public function filter(callable $callback): self
    {
        $this->filterCallback = $callback;

        return $this;
    }

    /**
     * Set a map callback to transform rows.
     */
    public function map(callable $callback): self
    {
        $this->mapCallback = $callback;

        return $this;
    }

    /**
     * Iterate over each row, executing the callback.
     */
    public function each(callable $callback): void
    {
        foreach ($this->rows() as $index => $row) {
            $callback($row, $index);
        }
    }

    /**
     * Collect all rows into an array.
     *
     * @return array<int, array<string|int, mixed>>
     */
    public function toArray(): array
    {
        return iterator_to_array($this->rows(), false);
    }

    /**
     * Count the number of rows.
     */
    public function count(): int
    {
        $count = 0;

        foreach ($this->rows() as $_) {
            $count++;
        }

        return $count;
    }

    /**
     * Get the iterator for the CSV rows.
     *
     * @return Traversable<int, array<string|int, mixed>>
     */
    public function getIterator(): Traversable
    {
        return $this->rows();
    }

    /**
     * Generate rows from the CSV stream.
     *
     * @return Generator<int, array<string|int, mixed>>
     *
     * @throws CsvReadException
     */
    private function rows(): Generator
    {
        if (! is_resource($this->stream)) {
            throw new CsvReadException('Stream is not a valid resource');
        }

        rewind($this->stream);

        /** @var array<int, string>|null $headers */
        $headers = null;

        if ($this->hasHeader) {
            $headerRow = fgetcsv($this->stream, 0, $this->delimiter, $this->enclosure, $this->escape);

            if ($headerRow === false) {
                throw new CsvReadException('Failed to read header row');
            }

            $headers = $headerRow;
        }

        $index = 0;

        while (($fields = fgetcsv($this->stream, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            if ($this->skipEmpty && $fields === [null]) {
                continue;
            }

            if ($this->castTypes) {
                $fields = array_map($this->castValue(...), $fields);
            }

            /** @var array<string|int, mixed> $row */
            $row = $headers !== null
                ? array_combine($headers, array_pad($fields, count($headers), null))
                : $fields;

            if ($this->filterCallback !== null && ! ($this->filterCallback)($row)) {
                continue;
            }

            if ($this->mapCallback !== null) {
                $row = ($this->mapCallback)($row);
            }

            yield $index => $row;
            $index++;
        }
    }

    /**
     * Cast a string value to its detected type.
     */
    private function castValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        if ($value === '') {
            return null;
        }

        $lower = strtolower($value);

        if ($lower === 'true') {
            return true;
        }

        if ($lower === 'false') {
            return false;
        }

        if (ctype_digit($value) || (str_starts_with($value, '-') && ctype_digit(substr($value, 1)))) {
            return (int) $value;
        }

        if (is_numeric($value) && str_contains($value, '.')) {
            return (float) $value;
        }

        return $value;
    }
}
