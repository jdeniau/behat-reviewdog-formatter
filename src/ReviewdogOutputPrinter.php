<?php

namespace JDeniau\ReviewdogFormatterExtension;

use Behat\Testwork\Output\Printer\OutputPrinter;

class ReviewdogOutputPrinter implements OutputPrinter
{
    private ?bool $isOutputDecorated;

    private ?string $outputPath = null;

    private string $fileName = 'reviewdog.json';

    public function __construct(private readonly string $pathBase)
    {
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * {@inheritdoc}
     */
    public function setOutputPath($path): void
    {
        $this->outputPath = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getOutputPath(): string
    {
        return $this->outputPath ?? $this->pathBase;
    }

    /**
     * {@inheritdoc}
     * @param array<mixed> $styles
     */
    public function setOutputStyles(array $styles): void
    {
    }

    /**
     * {@inheritdoc}
     * @return array<mixed>
     */
    public function getOutputStyles()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function setOutputDecorated($decorated): void
    {
        $this->isOutputDecorated = (bool) $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function isOutputDecorated()
    {
        return $this->isOutputDecorated;
    }

    /**
     * {@inheritdoc}
     */
    public function setOutputVerbosity($level): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getOutputVerbosity()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     * @param string|array<string> $messages
     */
    public function write($messages): void
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $this->doWrite($messages, false);
    }

    /**
     * {@inheritdoc}
     * @param string|array<string> $messages
     */
     
    public function writeln($messages = ''): void
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $this->doWrite($messages, true);
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): void
    {
    }

    public function removeOldFile(): void
    {
        $filePath = $this->getFilePath();

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * @param array<string> $messages
     */
    private function doWrite(array $messages, bool $append): void
    {
        if (!is_dir($this->getOutputPath())) {
            mkdir($this->getOutputPath(), 0777, true);
        }

        file_put_contents($this->getFilePath(), implode("\n", $messages) . "\n", $append ? \FILE_APPEND : 0);
    }

    private function getFilePath(): string
    {
        return $this->getOutputPath() . '/' . $this->fileName;
    }
}
