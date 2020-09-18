# Changelog

All notable changes to `php-csv` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-03-22

### Added
- `firstRow()` method on CsvReader — returns the first data row or null if empty
- `lastRow()` method on CsvReader — returns the last data row or null if empty
- `groupBy(string $column)` method on CsvReader — returns rows grouped by column value
- `appendToFile(string $path)` method on CsvWriter — appends rows to an existing CSV file without writing headers again

## [1.1.2] - 2026-03-20

### Added
- Expanded test suite with StreamingWriter edge cases and exception coverage

## [1.1.1] - 2026-03-17

### Changed
- Standardized package metadata, README structure, and CI workflow per package guide

## [1.1.0] - 2026-03-16

### Added
- Row validation via `validate(callable)` on CsvReader — skip invalid rows and collect errors
- Progress tracking via `withProgress(callable)` on CsvReader — callback after each row
- `getValidationErrors()` method to retrieve validation failures from the last read
- `StreamingWriter` class for writing rows directly to disk without buffering
- `Csv::streamWrite()` static factory method for creating streaming writers

## [1.0.2] - 2026-03-16

### Changed
- Standardize composer.json: add type, homepage, scripts

## [1.0.1] - 2026-03-15

### Changed
- Standardize README badges

## [1.0.0] - 2026-03-15

### Added
- Initial release
- Memory-efficient generator-based CSV reader
- Fluent CSV writer with BOM support
- Header mapping for associative array output
- Automatic type casting (int, float, bool, null)
- Configurable delimiter, enclosure, and escape characters
- Filter, map, and each operations on reader
- String-based reading via `php://temp` streams
