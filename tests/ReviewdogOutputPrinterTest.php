<?php

declare(strict_types=1);

namespace JDeniau\BehatReviewdogFormatter\Tests;

use JDeniau\BehatReviewdogFormatter\ReviewdogOutputPrinter;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;

/**
 * @covers \JDeniau\BehatReviewdogFormatter\ReviewdogOutputPrinter
 */
class ReviewdogOutputPrinterTest extends MockeryTestCase
{
    use PHPMock;

    /**
     * @dataProvider removeOldFileDataProvider
     */
    public function testRemoveOldFile(
        bool $fileExists,
        int $unlink,
        ?string $filename
    ): void {
        $outputPrinter = new ReviewdogOutputPrinter('/tmp');

        if ($filename) {
            $outputPrinter->setFileName($filename);
        }

        $fileExistsMock = $this->getFunctionMock(
            'JDeniau\BehatReviewdogFormatter',
            'file_exists'
        );
        $unlinkMock = $this->getFunctionMock(
            'JDeniau\BehatReviewdogFormatter',
            'unlink'
        );

        $expectedFilename = $filename ?? 'reviewdog-behat.json';

        $fileExistsMock
            ->expects($this->once())
            ->willReturn($fileExists)
            ->with("/tmp/{$expectedFilename}");
        $unlinkMock
            ->expects($this->exactly($unlink))
            ->with("/tmp/{$expectedFilename}");

        $outputPrinter->removeOldFile();
    }

    public static function removeOldFileDataProvider(): iterable
    {
        yield 'file does not exist' => [
            'fileExists' => false,
            'unlink' => 0,
            'filename' => null,
        ];
        yield 'file does exist' => [
            'fileExists' => true,
            'unlink' => 1,
            'filename' => null,
        ];
        yield 'with a custom filename' => [
            'fileExists' => false,
            'unlink' => 0,
            'filename' => 'output.json',
        ];
    }

    public function testWrite(): void
    {
        $outputPrinter = new ReviewdogOutputPrinter('/tmp');

        $filePutContentsMock = $this->getFunctionMock(
            'JDeniau\BehatReviewdogFormatter',
            'file_put_contents'
        );

        $filePutContentsMock
            ->expects($this->exactly(4))
            ->willReturnCallback(function ($filePath, $data, $flags) {
                static $call = 0;

                $call++;

                switch ($call) {
                    case 1:
                        $this->assertSame(
                            '/tmp/reviewdog-behat.json',
                            $filePath
                        );
                        $this->assertSame("line1\nline2\n", $data);
                        $this->assertSame(0, $flags);
                        break;
                    case 2:
                        $this->assertSame(
                            '/tmp/reviewdog-behat.json',
                            $filePath
                        );
                        $this->assertSame("line3\nline4\n", $data);
                        $this->assertSame(\FILE_APPEND, $flags);
                        break;

                    case 3:
                        $this->assertSame(
                            '/tmp/reviewdog-behat.json',
                            $filePath
                        );
                        $this->assertSame("single line 1\n", $data);
                        $this->assertSame(0, $flags);
                        break;
                    case 4:
                        $this->assertSame(
                            '/tmp/reviewdog-behat.json',
                            $filePath
                        );
                        $this->assertSame("single line 2\n", $data);
                        $this->assertSame(\FILE_APPEND, $flags);
                        break;
                    default:
                        throw new \Exception(
                            "Unexpected call to file_put_contents with parameters: {$filePath}, {$data}, {$flags}"
                        );
                }
            });

        $outputPrinter->write(['line1', 'line2']);
        $outputPrinter->writeln(['line3', 'line4']);

        $outputPrinter->write('single line 1');
        $outputPrinter->writeln('single line 2');
    }
}
