<?php

declare(strict_types=1);

namespace PhilipRehberger\Csv\Tests;

use PhilipRehberger\Csv\Csv;
use PhilipRehberger\Csv\Exceptions\CsvReadException;
use PHPUnit\Framework\TestCase;

class CsvTest extends TestCase
{
    /** @var array<int, string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $this->tempFiles = [];
    }

    private function tempFile(string $content = ''): string
    {
        $path = sys_get_temp_dir().'/php_csv_test_'.uniqid().'.csv';
        $this->tempFiles[] = $path;

        if ($content !== '') {
            file_put_contents($path, $content);
        }

        return $path;
    }

    public function test_read_csv_with_headers(): void
    {
        $csv = "name,age,city\nAlice,30,Berlin\nBob,25,Vienna\n";
        $rows = Csv::readString($csv)->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
        $this->assertSame('30', $rows[0]['age']);
        $this->assertSame('Berlin', $rows[0]['city']);
        $this->assertSame('Bob', $rows[1]['name']);
    }

    public function test_read_csv_without_headers(): void
    {
        $csv = "Alice,30,Berlin\nBob,25,Vienna\n";
        $rows = Csv::readString($csv)->hasHeader(false)->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0][0]);
        $this->assertSame('30', $rows[0][1]);
    }

    public function test_read_csv_with_type_casting(): void
    {
        $csv = "name,age,score,active,note\nAlice,42,3.14,true,\nBob,25,9.99,false,hello\n";
        $rows = Csv::readString($csv)->castTypes(true)->toArray();

        $this->assertSame(42, $rows[0]['age']);
        $this->assertSame(3.14, $rows[0]['score']);
        $this->assertTrue($rows[0]['active']);
        $this->assertNull($rows[0]['note']);
        $this->assertSame(25, $rows[1]['age']);
        $this->assertFalse($rows[1]['active']);
        $this->assertSame('hello', $rows[1]['note']);
    }

    public function test_read_csv_with_custom_delimiter(): void
    {
        $csv = "name;age;city\nAlice;30;Berlin\n";
        $rows = Csv::readString($csv)->delimiter(';')->toArray();

        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
        $this->assertSame('Berlin', $rows[0]['city']);
    }

    public function test_read_csv_skips_empty_rows(): void
    {
        $csv = "name,age\nAlice,30\n\nBob,25\n";
        $rows = Csv::readString($csv)->skipEmpty(true)->toArray();

        $this->assertCount(2, $rows);
    }

    public function test_read_csv_filter(): void
    {
        $csv = "name,age\nAlice,30\nBob,25\nCharlie,35\n";
        $rows = Csv::readString($csv)
            ->castTypes(true)
            ->filter(fn (array $row): bool => $row['age'] >= 30)
            ->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
        $this->assertSame('Charlie', $rows[1]['name']);
    }

    public function test_read_csv_map(): void
    {
        $csv = "name,age\nAlice,30\nBob,25\n";
        $rows = Csv::readString($csv)
            ->map(fn (array $row): array => [...$row, 'upper' => strtoupper($row['name'])])
            ->toArray();

        $this->assertSame('ALICE', $rows[0]['upper']);
        $this->assertSame('BOB', $rows[1]['upper']);
    }

    public function test_read_csv_each(): void
    {
        $csv = "name\nAlice\nBob\n";
        $names = [];

        Csv::readString($csv)->each(function (array $row) use (&$names): void {
            $names[] = $row['name'];
        });

        $this->assertSame(['Alice', 'Bob'], $names);
    }

    public function test_read_csv_count(): void
    {
        $csv = "name,age\nAlice,30\nBob,25\nCharlie,35\n";
        $count = Csv::readString($csv)->count();

        $this->assertSame(3, $count);
    }

    public function test_read_csv_from_file(): void
    {
        $path = $this->tempFile("name,age\nAlice,30\n");
        $rows = Csv::read($path)->toArray();

        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
    }

    public function test_read_csv_file_not_found(): void
    {
        $this->expectException(CsvReadException::class);
        $this->expectExceptionMessage('File not found');

        Csv::read('/nonexistent/path/file.csv');
    }

    public function test_write_csv_to_file(): void
    {
        $path = $this->tempFile();

        Csv::write($path)
            ->headers(['name', 'age'])
            ->row(['name' => 'Alice', 'age' => 30])
            ->row(['name' => 'Bob', 'age' => 25])
            ->save();

        $content = file_get_contents($path);
        $this->assertIsString($content);
        $this->assertStringContainsString('name,age', $content);
        $this->assertStringContainsString('Alice,30', $content);
        $this->assertStringContainsString('Bob,25', $content);
    }

    public function test_write_csv_to_string(): void
    {
        $output = Csv::write('')
            ->headers(['name', 'age'])
            ->rows([
                ['name' => 'Alice', 'age' => 30],
                ['name' => 'Bob', 'age' => 25],
            ])
            ->toString();

        $this->assertStringContainsString('name,age', $output);
        $this->assertStringContainsString('Alice,30', $output);
    }

    public function test_write_csv_with_bom(): void
    {
        $output = Csv::write('')
            ->headers(['name'])
            ->row(['name' => 'Alice'])
            ->bom(true)
            ->toString();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $output);
    }

    public function test_reader_is_iterable(): void
    {
        $csv = "name\nAlice\nBob\n";
        $reader = Csv::readString($csv);

        $names = [];
        foreach ($reader as $row) {
            $names[] = $row['name'];
        }

        $this->assertSame(['Alice', 'Bob'], $names);
    }

    public function test_first_row_returns_first_data_row(): void
    {
        $csv = "name,age\nAlice,30\nBob,25\n";
        $row = Csv::readString($csv)->firstRow();

        $this->assertNotNull($row);
        $this->assertSame('Alice', $row['name']);
        $this->assertSame('30', $row['age']);
    }

    public function test_first_row_returns_null_for_empty_csv(): void
    {
        $csv = "name,age\n";
        $row = Csv::readString($csv)->firstRow();

        $this->assertNull($row);
    }

    public function test_last_row_returns_last_data_row(): void
    {
        $csv = "name,age\nAlice,30\nBob,25\nCharlie,35\n";
        $row = Csv::readString($csv)->lastRow();

        $this->assertNotNull($row);
        $this->assertSame('Charlie', $row['name']);
        $this->assertSame('35', $row['age']);
    }

    public function test_last_row_returns_null_for_empty_csv(): void
    {
        $csv = "name,age\n";
        $row = Csv::readString($csv)->lastRow();

        $this->assertNull($row);
    }

    public function test_group_by_column(): void
    {
        $csv = "name,city\nAlice,Berlin\nBob,Vienna\nCharlie,Berlin\nDave,Vienna\n";
        $groups = Csv::readString($csv)->groupBy('city');

        $this->assertCount(2, $groups);
        $this->assertArrayHasKey('Berlin', $groups);
        $this->assertArrayHasKey('Vienna', $groups);
        $this->assertCount(2, $groups['Berlin']);
        $this->assertCount(2, $groups['Vienna']);
        $this->assertSame('Alice', $groups['Berlin'][0]['name']);
        $this->assertSame('Charlie', $groups['Berlin'][1]['name']);
        $this->assertSame('Bob', $groups['Vienna'][0]['name']);
        $this->assertSame('Dave', $groups['Vienna'][1]['name']);
    }

    public function test_group_by_with_missing_column(): void
    {
        $csv = "name,city\nAlice,Berlin\nBob,Vienna\n";
        $groups = Csv::readString($csv)->groupBy('country');

        $this->assertCount(1, $groups);
        $this->assertArrayHasKey('', $groups);
        $this->assertCount(2, $groups['']);
    }

    public function test_append_to_file_preserves_existing_content(): void
    {
        $path = $this->tempFile("name,age\nAlice,30\n");

        Csv::write($path)
            ->headers(['name', 'age'])
            ->row(['name' => 'Bob', 'age' => 25])
            ->row(['name' => 'Charlie', 'age' => 35])
            ->appendToFile($path);

        $rows = Csv::read($path)->toArray();

        $this->assertCount(3, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
        $this->assertSame('Bob', $rows[1]['name']);
        $this->assertSame('Charlie', $rows[2]['name']);
    }

    public function test_read_tsv_and_write_tsv(): void
    {
        $output = Csv::writeTsv()
            ->headers(['name', 'age', 'city'])
            ->row(['name' => 'Alice', 'age' => 30, 'city' => 'Berlin'])
            ->row(['name' => 'Bob', 'age' => 25, 'city' => 'Vienna'])
            ->toString();

        $this->assertStringContainsString("name\tage\tcity", $output);
        $this->assertStringContainsString("Alice\t30\tBerlin", $output);

        $path = $this->tempFile($output);
        $rows = Csv::readTsv($path)->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
        $this->assertSame('30', $rows[0]['age']);
        $this->assertSame('Berlin', $rows[0]['city']);
        $this->assertSame('Bob', $rows[1]['name']);
    }

    public function test_read_psv_and_write_psv(): void
    {
        $output = Csv::writePsv()
            ->headers(['name', 'age', 'city'])
            ->row(['name' => 'Alice', 'age' => 30, 'city' => 'Berlin'])
            ->row(['name' => 'Bob', 'age' => 25, 'city' => 'Vienna'])
            ->toString();

        $this->assertStringContainsString('name|age|city', $output);
        $this->assertStringContainsString('Alice|30|Berlin', $output);

        $path = $this->tempFile($output);
        $rows = Csv::readPsv($path)->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
        $this->assertSame('30', $rows[0]['age']);
        $this->assertSame('Berlin', $rows[0]['city']);
        $this->assertSame('Bob', $rows[1]['name']);
    }

    public function test_transform_column_applies_transformation(): void
    {
        $csv = "name,age,city\nAlice,30,Berlin\nBob,25,Vienna\n";
        $rows = Csv::readString($csv)
            ->transformColumn('name', fn (string $value): string => strtoupper($value))
            ->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('ALICE', $rows[0]['name']);
        $this->assertSame('30', $rows[0]['age']);
        $this->assertSame('BOB', $rows[1]['name']);
    }

    public function test_transform_column_multiple_columns(): void
    {
        $csv = "name,age,city\nAlice,30,Berlin\n";
        $rows = Csv::readString($csv)
            ->transformColumn('name', fn (string $value): string => strtoupper($value))
            ->transformColumn('city', fn (string $value): string => strtolower($value))
            ->toArray();

        $this->assertSame('ALICE', $rows[0]['name']);
        $this->assertSame('berlin', $rows[0]['city']);
    }

    public function test_detect_duplicates_returns_correct_indices(): void
    {
        $csv = "name,email\nAlice,alice@example.com\nBob,bob@example.com\nCharlie,alice@example.com\nDave,dave@example.com\nEve,bob@example.com\n";
        $duplicates = Csv::readString($csv)->detectDuplicates(['email']);

        $this->assertSame([2, 4], $duplicates);
    }

    public function test_detect_duplicates_no_duplicates(): void
    {
        $csv = "name,email\nAlice,alice@example.com\nBob,bob@example.com\n";
        $duplicates = Csv::readString($csv)->detectDuplicates(['email']);

        $this->assertSame([], $duplicates);
    }

    public function test_detect_duplicates_multiple_columns(): void
    {
        $csv = "first,last,age\nAlice,Smith,30\nBob,Jones,25\nAlice,Smith,35\nAlice,Jones,30\n";
        $duplicates = Csv::readString($csv)->detectDuplicates(['first', 'last']);

        $this->assertSame([2], $duplicates);
    }

    public function test_read_quoted_field_with_embedded_delimiter(): void
    {
        $csv = "name,description\nAlice,\"hello, world\"\nBob,\"a,b,c\"\n";
        $rows = Csv::readString($csv)->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('hello, world', $rows[0]['description']);
        $this->assertSame('a,b,c', $rows[1]['description']);
    }

    public function test_read_quoted_field_with_embedded_newline(): void
    {
        $csv = "name,note\nAlice,\"line one\nline two\"\nBob,plain\n";
        $rows = Csv::readString($csv)->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame("line one\nline two", $rows[0]['note']);
        $this->assertSame('plain', $rows[1]['note']);
    }

    public function test_custom_enclosure_round_trip(): void
    {
        $output = Csv::write('')
            ->headers(['name', 'description'])
            ->enclosure("'")
            ->row(['name' => 'Alice', 'description' => 'hello, world'])
            ->row(['name' => 'Bob', 'description' => "a'b"])
            ->toString();

        $path = $this->tempFile($output);
        $rows = Csv::read($path)->enclosure("'")->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
        $this->assertSame('hello, world', $rows[0]['description']);
        $this->assertSame('Bob', $rows[1]['name']);
    }

    public function test_custom_escape_character_round_trip(): void
    {
        $output = Csv::write('')
            ->headers(['name', 'value'])
            ->escape('|')
            ->row(['name' => 'Alice', 'value' => 'plain'])
            ->row(['name' => 'Bob', 'value' => 'with,comma'])
            ->toString();

        $path = $this->tempFile($output);
        $rows = Csv::read($path)->escape('|')->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
        $this->assertSame('plain', $rows[0]['value']);
        $this->assertSame('Bob', $rows[1]['name']);
        $this->assertSame('with,comma', $rows[1]['value']);
    }

    public function test_append_to_file_does_not_write_headers(): void
    {
        $path = $this->tempFile("name,age\nAlice,30\n");

        Csv::write($path)
            ->headers(['name', 'age'])
            ->row(['name' => 'Bob', 'age' => 25])
            ->appendToFile($path);

        $content = file_get_contents($path);
        $this->assertIsString($content);

        // Headers should appear only once (from the original file)
        $this->assertSame(1, substr_count($content, 'name,age'));
    }
}
