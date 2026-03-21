<?php

declare(strict_types=1);

namespace PhilipRehberger\Csv\Tests;

use PhilipRehberger\Csv\Csv;
use PhilipRehberger\Csv\Exceptions\CsvWriteException;
use PhilipRehberger\Csv\StreamingWriter;
use PHPUnit\Framework\TestCase;

class StreamingWriterTest extends TestCase
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

    public function test_double_close_does_not_throw(): void
    {
        $path = $this->tempFile();
        $writer = Csv::streamWrite($path);

        $writer->writeHeader(['name', 'age']);
        $writer->close();
        $writer->close();

        $this->assertTrue(true, 'Double close should not throw an exception');
    }

    public function test_write_header_after_close_throws(): void
    {
        $path = $this->tempFile();
        $writer = Csv::streamWrite($path);

        $writer->close();

        $this->expectException(CsvWriteException::class);
        $this->expectExceptionMessage('Writer has been closed');

        $writer->writeHeader(['name', 'age']);
    }

    public function test_write_row_after_close_throws(): void
    {
        $path = $this->tempFile();
        $writer = Csv::streamWrite($path);

        $writer->close();

        $this->expectException(CsvWriteException::class);
        $this->expectExceptionMessage('Writer has been closed');

        $writer->writeRow(['Alice', 30]);
    }

    public function test_write_rows_after_close_throws(): void
    {
        $path = $this->tempFile();
        $writer = Csv::streamWrite($path);

        $writer->close();

        $this->expectException(CsvWriteException::class);
        $this->expectExceptionMessage('Writer has been closed');

        $writer->writeRows([['Alice', 30], ['Bob', 25]]);
    }

    public function test_constructor_throws_for_nonexistent_directory(): void
    {
        $this->expectException(CsvWriteException::class);
        $this->expectExceptionMessage('Directory does not exist');

        new StreamingWriter('/nonexistent/directory/file.csv');
    }

    public function test_is_header_written_returns_false_initially(): void
    {
        $path = $this->tempFile();
        $writer = Csv::streamWrite($path);

        $this->assertFalse($writer->isHeaderWritten());

        $writer->close();
    }

    public function test_is_header_written_returns_true_after_writing_header(): void
    {
        $path = $this->tempFile();
        $writer = Csv::streamWrite($path);

        $writer->writeHeader(['name', 'age']);

        $this->assertTrue($writer->isHeaderWritten());

        $writer->close();
    }

    public function test_large_write_produces_correct_output(): void
    {
        $path = $this->tempFile();
        $writer = Csv::streamWrite($path);

        $writer->writeHeader(['id', 'value']);

        for ($i = 0; $i < 1000; $i++) {
            $writer->writeRow([$i, "value_{$i}"]);
        }

        $writer->close();

        $rows = Csv::read($path)->toArray();

        $this->assertCount(1000, $rows);
        $this->assertSame('0', $rows[0]['id']);
        $this->assertSame('value_0', $rows[0]['value']);
        $this->assertSame('999', $rows[999]['id']);
        $this->assertSame('value_999', $rows[999]['value']);
    }

    public function test_write_rows_batch(): void
    {
        $path = $this->tempFile();
        $writer = Csv::streamWrite($path);

        $writer->writeHeader(['name', 'age']);
        $writer->writeRows([
            ['Alice', 30],
            ['Bob', 25],
            ['Charlie', 35],
        ]);

        $writer->close();

        $rows = Csv::read($path)->toArray();

        $this->assertCount(3, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
        $this->assertSame('Charlie', $rows[2]['name']);
    }

    public function test_write_with_custom_delimiter(): void
    {
        $path = $this->tempFile();
        $writer = Csv::streamWrite($path, ';');

        $writer->writeHeader(['name', 'age']);
        $writer->writeRow(['Alice', 30]);

        $writer->close();

        $rows = Csv::read($path)->delimiter(';')->toArray();

        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
        $this->assertSame('30', $rows[0]['age']);
    }

    public function test_write_row_with_special_characters(): void
    {
        $path = $this->tempFile();
        $writer = Csv::streamWrite($path);

        $writer->writeHeader(['name', 'note']);
        $writer->writeRow(['Alice', 'has a "quote" inside']);
        $writer->writeRow(['Bob', "line1\nline2"]);
        $writer->writeRow(['Charlie', 'value,with,commas']);

        $writer->close();

        $rows = Csv::read($path)->toArray();

        $this->assertCount(3, $rows);
        $this->assertSame('has a "quote" inside', $rows[0]['note']);
        $this->assertSame("line1\nline2", $rows[1]['note']);
        $this->assertSame('value,with,commas', $rows[2]['note']);
    }

    public function test_write_without_header(): void
    {
        $path = $this->tempFile();
        $writer = Csv::streamWrite($path);

        $writer->writeRow(['Alice', 30]);
        $writer->writeRow(['Bob', 25]);

        $writer->close();

        $rows = Csv::read($path)->hasHeader(false)->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0][0]);
        $this->assertSame('30', $rows[0][1]);
    }

    public function test_write_empty_file(): void
    {
        $path = $this->tempFile();
        $writer = Csv::streamWrite($path);

        $writer->close();

        $content = file_get_contents($path);
        $this->assertSame('', $content);
    }

    public function test_write_header_only(): void
    {
        $path = $this->tempFile();
        $writer = Csv::streamWrite($path);

        $writer->writeHeader(['name', 'age']);

        $writer->close();

        $rows = Csv::read($path)->toArray();

        $this->assertCount(0, $rows);
    }

    public function test_destructor_closes_handle(): void
    {
        $path = $this->tempFile();
        $writer = Csv::streamWrite($path);

        $writer->writeHeader(['name']);
        $writer->writeRow(['Alice']);

        unset($writer);

        $rows = Csv::read($path)->toArray();

        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
    }

    public function test_csv_write_exception_extends_runtime_exception(): void
    {
        $exception = new CsvWriteException('test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame('test message', $exception->getMessage());
    }

    public function test_csv_write_exception_with_code_and_previous(): void
    {
        $previous = new \Exception('previous');
        $exception = new CsvWriteException('test message', 42, $previous);

        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
