<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\PdfCreator\Concrete;

use HeimrichHannot\PdfCreator\AbstractPdfCreator;
use HeimrichHannot\PdfCreator\BeforeCreateLibraryInstanceCallback;
use HeimrichHannot\PdfCreator\BeforeOutputPdfCallback;
use HeimrichHannot\PdfCreator\Exception\MissingDependenciesException;
use HeimrichHannot\PdfCreator\PdfCreatorResult;
use setasign\Fpdi\Tcpdf\Fpdi;
use TCPDF;

class TcpdfCreator extends AbstractPdfCreator
{
    public static function getType(): string
    {
        return 'tcpdf';
    }

    public static function isUsable(bool $triggerExeption = false): bool
    {
        if (!class_exists('TCPDF')) {
            if ($triggerExeption) {
                throw new MissingDependenciesException(static::getType(), ['"tecnickcom/tcpdf": "^6.3"']);
            }

            return false;
        }

        return true;
    }

    /**
     * @throws MissingDependenciesException
     *
     * @return string|void
     */
    public function render(): PdfCreatorResult
    {
        static::isUsable(true);

        $orientation = '';

        if ($this->getOrientation()) {
            switch ($this->getOrientation()) {
                case static::ORIENTATION_PORTRAIT:
                    $orientation = 'P';

                    break;

                case static::ORIENTATION_LANDSCAPE:
                    $orientation = 'L';

                    break;
            }
        }

        $format = $this->getFormat() ?: 'A4';

        $constructorParams = [
            'orientation' => $orientation,
            'unit' => 'mm',
            'format' => $format,
            'unicode' => true,
            'encoding' => 'UTF-8',
            'diskcache' => false,
            'pdfa' => false,
        ];

        if ($this->getBeforeCreateInstanceCallback()) {
            /** @var BeforeCreateLibraryInstanceCallback $callback */
            $callback = \call_user_func($this->getBeforeCreateInstanceCallback(), new BeforeCreateLibraryInstanceCallback(static::getType(), $constructorParams));

            if ($callback && \is_array($callback->getConstructorParameters())) {
                $constructorParams = array_merge($constructorParams, $callback->getConstructorParameters());
            }
        }

        if (class_exists('setasign\Fpdi\Tcpdf\Fpdi')) {
            $pdf = new Fpdi(
                $constructorParams['orientation'],
                $constructorParams['unit'],
                $constructorParams['format'],
                $constructorParams['unicode'],
                $constructorParams['encoding'],
                $constructorParams['diskcache'],
                $constructorParams['pdfa']
            );
        } else {
            $pdf = new TCPDF(
                $constructorParams['orientation'],
                $constructorParams['unit'],
                $constructorParams['format'],
                $constructorParams['unicode'],
                $constructorParams['encoding'],
                $constructorParams['diskcache'],
                $constructorParams['pdfa']
            );
        }

        // Prevent font subsetting (huge speed improvement)
        $pdf->setFontSubsetting(false);

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $masterTemplate = null;

        if ('setasign\Fpdi\Tcpdf\Fpdi' == \get_class($pdf) && $this->getTemplateFilePath()) {
            if (file_exists($this->getTemplateFilePath())) {
                $pdf->setSourceFile($this->getTemplateFilePath());
                $masterTemplate = $pdf->importPage(1);
                $pdf->useImportedPage($masterTemplate, 10, 10, 100);
            } else {
                trigger_error('Pdf template does not exist.', \E_USER_NOTICE);
            }
        }

        if ($this->getMargins()) {
            if ($this->getMargins()['top']) {
                $pdf->SetTopMargin($this->getMargins()['top']);
            }

            if ($this->getMargins()['right']) {
                $pdf->SetRightMargin($this->getMargins()['right']);
            }

//            if ($this->getMargins()['bottom']) {
//                $config['margin_bottom'] = $this->getMargins()['bottom'];
//            }

            if ($this->getMargins()['left']) {
                $pdf->SetLeftMargin($this->getMargins()['left']);
            }
        }

        if ($this->getFonts()) {
            foreach ($this->getFonts() as $font) {
                if ('ttf' === pathinfo($font['filepath'], \PATHINFO_EXTENSION)) {
                    $filename = \TCPDF_FONTS::addTTFfont($font['filepath']);
                    $pdf->AddFont($filename);
                }
            }
        }

        $pdf->AddPage();

        if ($this->getHtmlContent()) {
            $pdf->WriteHTML($this->getHtmlContent());
        }

        $outputMode = '';
        $filename = $this->getFilename() ?: '';

        switch ($this->getOutputMode()) {
            case static::OUTPUT_MODE_STRING:
                $outputMode = 'S';

                break;

            case static::OUTPUT_MODE_FILE:
                if (($folder = $this->getFolder()) && $this->getFilename()) {
                    $filename = rtrim($folder, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.$filename;
                }

                $outputMode = 'F';

                break;

            case static::OUTPUT_MODE_DOWNLOAD:
                $outputMode = 'D';

                break;

            case static::OUTPUT_MODE_INLINE:
                $outputMode = 'I';

                break;
        }

        if ($masterTemplate) {
            $pageCount = $pdf->getNumPages();

            for ($i = 1; $i <= $pageCount; ++$i) {
                $pdf->setPage($i);
                $pdf->useTemplate($masterTemplate);
            }
        }

        $pdf->lastPage();

        if ($this->getBeforeOutputPdfCallback()) {
            /** @var BeforeOutputPdfCallback $callback */
            $callback = \call_user_func($this->getBeforeOutputPdfCallback(), new BeforeOutputPdfCallback(static::getType(), $pdf, [
                'name' => $filename,
                'dest' => $outputMode,
            ]));

            if ($callback) {
                if (isset($callback->getOutputParameters()['name'])) {
                    $filename = $callback->getOutputParameters()['name'];
                }

                if (isset($callback->getOutputParameters()['dest'])) {
                    $outputMode = $callback->getOutputParameters()['dest'];
                }
            }
        }

        $result = new PdfCreatorResult($this->getOutputMode());

        switch ($this->getOutputMode()) {
            case static::OUTPUT_MODE_STRING:
                $result->setFileContent($pdf->Output($filename, $outputMode));

                break;

            case static::OUTPUT_MODE_FILE:
                $result->setFilePath($filename);
                $pdf->Output($filename, $outputMode);

                break;

            default:
                $pdf->Output($filename, $outputMode);
        }

        return $result;
    }

    public function getSupportedOutputModes(): array
    {
        return [
            self::OUTPUT_MODE_DOWNLOAD,
            self::OUTPUT_MODE_FILE,
            self::OUTPUT_MODE_INLINE,
            self::OUTPUT_MODE_STRING,
        ];
    }

    public function supports(): array
    {
        $support = [
            static::SUPPORT_FONTS,
            static::SUPPORT_MARGINS,
        ];

        if (class_exists('setasign\Fpdi\Tcpdf\Fpdi')) {
            $support[] = static::SUPPORT_MASTERTEMPLATE;
        }

        return $support;
    }
}
