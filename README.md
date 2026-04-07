# PHP CSV

[![Tests](https://github.com/philiprehberger/php-csv/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/php-csv/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/php-csv.svg)](https://packagist.org/packages/philiprehberger/php-csv)
[![Last updated](https://img.shields.io/github/last-commit/philiprehberger/php-csv)](https://github.com/philiprehberger/php-csv/commits/main)

Memory-efficient CSV reader and writer with header mapping and type casting.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require philiprehberger/php-csv
```

## Usage

### Reading a CSV file

```php
use PhilipRehberger\Csv\Csv;

// Read from file with headers
$rows = Csv::read('data.csv')->toArray();
// [['name' => 'Alice', 'age' => '30'], ...]

// Read from string
$rows = Csv::readString($csvContent)->toArray();
```

### Generator-based iteration

The reader uses PHP generators for memory-efficient processing of large files:

```php
foreach (Csv::read('large-file.csv') as $row) {
    // Process one row at a time — constant memory usage
}
```

### Type casting

Automatically detect and cast value types:

```php
$rows = Csv::read('data.csv')
    ->castTypes(true)
    ->toArray();
// "42" -> int, "3.14" -> float, "true"/"false" -> bool, "" -> null
```

### Filtering and mapping

```php
$rows = Csv::read('data.csv')
    ->castTypes(true)
    ->filter(fn (array $row) => $row['age'] >= 18)
    ->map(fn (array $row) => [...$row, 'label' => strtoupper($row['name'])])
    ->toArray();
```

### Row Validation

Validate each row during reading. Invalid rows are skipped and errors are collected:

```php
$reader = Csv::read('data.csv')
    ->validate(fn (array $row) => isset($row['email']) && str_contains($row['email'], '@'));

$rows = $reader->toArray();

// Inspect which rows failed validation
foreach ($reader->getValidationErrors() as $error) {
    echo "Row {$error['row']}: {$error['error']}\n";
}
```

The validator can also throw an exception to provide a specific error message:

```php
$reader = Csv::read('data.csv')
    ->validate(function (array $row) {
        if (empty($row['name'])) {
            throw new \InvalidArgumentException('Name is required');
        }
        return true;
    });
```

### Progress Tracking

Monitor processing progress with a callback invoked after each row:

```php
Csv::read('large-file.csv')
    ->withProgress(function (int $rowNumber) {
        if ($rowNumber % 1000 === 0) {
            echo "Processed {$rowNumber} rows...\n";
        }
    })
    ->each(fn (array $row) => processRow($row));
```

### First and last rows

Quickly access the first or last data row without loading everything into an array:

```php
$first = Csv::read('data.csv')->firstRow();
// ['name' => 'Alice', 'age' => '30', ...]

$last = Csv::read('data.csv')->lastRow();
// ['name' => 'Zoe', 'age' => '28', ...]
```

Both methods return `null` if the CSV has no data rows.

### Grouping rows

Group rows by a column value into an associative array:

```php
$groups = Csv::read('data.csv')->groupBy('city');
// ['Berlin' => [['name' => 'Alice', ...], ...], 'Vienna' => [...]]
```

### Column transformation

Apply per-column transformations during reading:

```php
$rows = Csv::read('data.csv')
    ->transformColumn('name', fn (string $value) => strtoupper($value))
    ->transformColumn('age', fn (string $value) => (int) $value)
    ->toArray();
```

### Duplicate detection

Find duplicate rows based on specific columns:

```php
$duplicates = Csv::read('data.csv')->detectDuplicates(['email']);
// [2, 5] — 0-based indices of duplicate rows
```

### Custom delimiters

```php
$rows = Csv::readString($tsv)
    ->delimiter("\t")
    ->toArray();
```

### TSV and PSV convenience methods

```php
// Tab-separated values
$rows = Csv::readTsv('data.tsv')->toArray();
$tsv = Csv::writeTsv()->headers(['name', 'age'])->rows($data)->toString();

// Pipe-separated values
$rows = Csv::readPsv('data.psv')->toArray();
$psv = Csv::writePsv()->headers(['name', 'age'])->rows($data)->toString();
```

### Writing CSV

```php
use PhilipRehberger\Csv\Csv;

Csv::write('output.csv')
    ->headers(['name', 'age', 'city'])
    ->row(['name' => 'Alice', 'age' => 30, 'city' => 'Berlin'])
    ->row(['name' => 'Bob', 'age' => 25, 'city' => 'Vienna'])
    ->save();

// Or get as string
$csv = Csv::write('')
    ->headers(['name', 'age'])
    ->rows($data)
    ->toString();
```

### Streaming Writer

Write rows directly to disk without buffering, ideal for very large files:

```php
use PhilipRehberger\Csv\Csv;

$writer = Csv::streamWrite('large-output.csv');
$writer->writeHeader(['id', 'name', 'value']);

foreach ($dataSource as $record) {
    $writer->writeRow([$record->id, $record->name, $record->value]);
}

$writer->close();
```

### Appending to an existing file

Append rows to an existing CSV without writing headers again:

```php
Csv::write('output.csv')
    ->headers(['name', 'age'])
    ->row(['name' => 'Charlie', 'age' => 35])
    ->appendToFile('output.csv');
```

### BOM for Excel

Prepend a UTF-8 BOM for Excel compatibility:

```php
Csv::write('output.csv')
    ->headers(['name', 'age'])
    ->rows($data)
    ->bom(true)
    ->save();
```

## API

### `Csv` (static entry)

| Method | Description |
|--------|-------------|
| `Csv::read(string $path): CsvReader` | Create a reader from a file path |
| `Csv::readString(string $content): CsvReader` | Create a reader from a string |
| `Csv::readTsv(string $path): CsvReader` | Create a TSV reader from a file path |
| `Csv::readPsv(string $path): CsvReader` | Create a PSV reader from a file path |
| `Csv::write(string $path): CsvWriter` | Create a writer for a file path |
| `Csv::writeTsv(): CsvWriter` | Create a TSV writer |
| `Csv::writePsv(): CsvWriter` | Create a PSV writer |
| `Csv::streamWrite(string $path, string $delimiter = ','): StreamingWriter` | Create a streaming writer for a file path |

### `CsvReader`

| Method | Description |
|--------|-------------|
| `delimiter(string $char): self` | Set the field delimiter (default `,`) |
| `enclosure(string $char): self` | Set the field enclosure (default `"`) |
| `escape(string $char): self` | Set the field escape character (default `\`) |
| `hasHeader(bool $flag): self` | Whether the first row is a header (default `true`) |
| `skipEmpty(bool $flag): self` | Skip empty rows (default `true`) |
| `castTypes(bool $flag): self` | Auto-detect types: int, float, bool, null |
| `filter(callable $fn): self` | Filter rows by a predicate |
| `map(callable $fn): self` | Transform each row |
| `validate(callable $fn): self` | Validate rows; invalid ones are skipped |
| `transformColumn(string $column, callable $fn): self` | Apply a transformer to a specific column |
| `detectDuplicates(array $columns): array` | Return 0-based indices of duplicate rows |
| `withProgress(callable $fn): self` | Set a progress callback (receives row number) |
| `getValidationErrors(): array` | Get errors from the last read |
| `each(callable $fn): void` | Execute a callback for each row |
| `toArray(): array` | Collect all rows into an array |
| `firstRow(): ?array` | Return the first data row or null |
| `lastRow(): ?array` | Return the last data row or null |
| `groupBy(string $column): array` | Group rows by a column value |
| `count(): int` | Count the number of rows |

### `CsvWriter`

| Method | Description |
|--------|-------------|
| `headers(array $headers): self` | Set column headers |
| `row(array $row): self` | Add a single row |
| `rows(array $rows): self` | Add multiple rows |
| `delimiter(string $char): self` | Set the field delimiter (default `,`) |
| `enclosure(string $char): self` | Set the field enclosure (default `"`) |
| `escape(string $char): self` | Set the field escape character (default `\`) |
| `bom(bool $flag): self` | Prepend UTF-8 BOM for Excel |
| `appendToFile(string $path): self` | Append rows to an existing file (no headers) |
| `save(): void` | Write to the configured file path |
| `toString(): string` | Return the CSV as a string |

### `StreamingWriter`

| Method | Description |
|--------|-------------|
| `enclosure(string $char): self` | Set the field enclosure (default `"`) |
| `escape(string $char): self` | Set the field escape character (default `\`) |
| `writeHeader(array $headers): void` | Write the header row |
| `writeRow(array $row): void` | Write a single data row |
| `writeRows(array $rows): void` | Write multiple data rows |
| `isHeaderWritten(): bool` | Whether the header has been written |
| `close(): void` | Close the file handle |

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## Support

If you find this project useful:

⭐ [Star the repo](https://github.com/philiprehberger/php-csv)

🐛 [Report issues](https://github.com/philiprehberger/php-csv/issues?q=is%3Aissue+is%3Aopen+label%3Abug)

💡 [Suggest features](https://github.com/philiprehberger/php-csv/issues?q=is%3Aissue+is%3Aopen+label%3Aenhancement)

❤️ [Sponsor development](https://github.com/sponsors/philiprehberger)

🌐 [All Open Source Projects](https://philiprehberger.com/open-source-packages)

💻 [GitHub Profile](https://github.com/philiprehberger)

🔗 [LinkedIn Profile](https://www.linkedin.com/in/philiprehberger)

## License

[MIT](LICENSE)
