# Changelog

All notable changes to `php-csv` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
