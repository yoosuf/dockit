<?php

namespace Yoosuf\Document\Documents\Services;

use Yoosuf\Document\Documents\Exceptions\DocumentException;
use Yoosuf\Document\Documents\Support\ErrorCodes;
use Symfony\Component\Process\Process;

class ConversionService
{
    public function convertDocToDocx(string $inputFile, string $outputDirectory): string
    {
        $this->runLibreOfficeConvert($inputFile, $outputDirectory, 'docx');

        return $outputDirectory.'/'.pathinfo($inputFile, PATHINFO_FILENAME).'.docx';
    }

    public function convertDocxToPdf(string $inputFile, string $outputDirectory): string
    {
        $this->runLibreOfficeConvert($inputFile, $outputDirectory, 'pdf');

        return $outputDirectory.'/'.pathinfo($inputFile, PATHINFO_FILENAME).'.pdf';
    }

    private function runLibreOfficeConvert(string $inputFile, string $outputDirectory, string $target): void
    {
        if (!is_file($inputFile)) {
            throw new DocumentException(ErrorCodes::DOCUMENT_CONVERSION_ERROR, 'Input file for conversion does not exist.', 500);
        }

        $binary = (string) config('document.libreoffice_binary', 'soffice');
        $timeout = (int) config('document.conversion_timeout_seconds', 60);

        $process = new Process([
            $binary,
            '--headless',
            '--convert-to',
            $target,
            '--outdir',
            $outputDirectory,
            $inputFile,
        ]);
        $process->setTimeout($timeout);

        try {
            $process->mustRun();
        } catch (\Throwable $exception) {
            $message = $process->getErrorOutput() ?: $process->getOutput();
            $code = str_contains(strtolower($message), 'not found') ? ErrorCodes::CONVERSION_UNAVAILABLE : ErrorCodes::DOCUMENT_CONVERSION_ERROR;
            $status = $code === ErrorCodes::CONVERSION_UNAVAILABLE ? 503 : 500;

            throw new DocumentException(
                $code,
                'Document conversion failed.',
                $status,
                previous: $exception,
            );
        }
    }
}
