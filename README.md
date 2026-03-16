# PHP CSV

[![Tests](https://github.com/philiprehberger/php-csv/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/php-csv/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/php-csv.svg)](https://packagist.org/packages/philiprehberger/php-csv)
[![PHP Version Require](https://img.shields.io/packagist/php-v/philiprehberger/php-csv.svg)](https://packagist.org/packages/philiprehberger/php-csv)
[![License](https://img.shields.io/github/license/philiprehberger/php-csv)](LICENSE)

Memory-efficient CSV reader and writer with header mapping and type casting.

---

## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | ^8.2    |

---

## Installation

```bash
composer require philiprehberger/php-csv
```

---

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

### Custom delimiters

```php
$rows = Csv::readString($tsv)
    ->delimiter("\t")
    ->toArray();
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

### BOM for Excel

Prepend a UTF-8 BOM for Excel compatibility:

```php
Csv::write('output.csv')
    ->headers(['name', 'age'])
    ->rows($data)
    ->bom(true)
    ->save();
```

---

## API

### `Csv` (static entry)

| Method | Description |
|--------|-------------|
| `Csv::read(string $path): CsvReader` | Create a reader from a file path |
| `Csv::readString(string $content): CsvReader` | Create a reader from a string |
| `Csv::write(string $path): CsvWriter` | Create a writer for a file path |

### `CsvReader`

| Method | Description |
|--------|-------------|
| `delimiter(string $char): self` | Set the field delimiter (default `,`) |
| `enclosure(string $char): self` | Set the field enclosure (default `"`) |
| `hasHeader(bool $flag): self` | Whether the first row is a header (default `true`) |
| `skipEmpty(bool $flag): self` | Skip empty rows (default `true`) |
| `castTypes(bool $flag): self` | Auto-detect types: int, float, bool, null |
| `filter(callable $fn): self` | Filter rows by a predicate |
| `map(callable $fn): self` | Transform each row |
| `each(callable $fn): void` | Execute a callback for each row |
| `toArray(): array` | Collect all rows into an array |
| `count(): int` | Count the number of rows |

### `CsvWriter`

| Method | Description |
|--------|-------------|
| `headers(array $headers): self` | Set column headers |
| `row(array $row): self` | Add a single row |
| `rows(array $rows): self` | Add multiple rows |
| `delimiter(string $char): self` | Set the field delimiter (default `,`) |
| `bom(bool $flag): self` | Prepend UTF-8 BOM for Excel |
| `save(): void` | Write to the configured file path |
| `toString(): string` | Return the CSV as a string |

---

## Testing

```bash
composer install
vendor/bin/phpunit
```

Code style:

```bash
vendor/bin/pint
```

Static analysis:

```bash
vendor/bin/phpstan analyse
```

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

---

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
