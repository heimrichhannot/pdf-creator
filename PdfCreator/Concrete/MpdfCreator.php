<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\PdfCreator\Concrete;

use Ausi\SlugGenerator\SlugGenerator;
use HeimrichHannot\PdfCreator\AbstractPdfCreator;
use HeimrichHannot\PdfCreator\BeforeCreateLibraryInstanceCallback;
use HeimrichHannot\PdfCreator\BeforeOutputPdfCallback;
use HeimrichHannot\PdfCreator\Exception\MissingDependenciesException;
use HeimrichHannot\PdfCreator\PdfCreatorResult;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class MpdfCreator extends AbstractPdfCreator
{
    /**
     * @var array
     */
    protected $legacyFontDirectoryConfig;

    public static function isUsable(bool $triggerExeption = false): bool
    {
        if (!class_exists('Mpdf\Mpdf')) {
            if ($triggerExeption) {
                throw new MissingDependenciesException(static::getType(), ['"mpdf/mpdf": "^8.0"']);
            }

            return false;
        }

        if (version_compare(Mpdf::VERSION, '7.0') < 0 || version_compare(Mpdf::VERSION, 9) >= 0) {
            if ($triggerExeption) {
                throw new MissingDependenciesException(static::getType(), ['"mpdf/mpdf": "^7.0|^8.0"']);
            }

            return false;
        }

        return true;
    }

    /**
     * @throws MissingDependenciesException
     */
    public function render(): PdfCreatorResult
    {
        static::isUsable(true);

        $config = [];

        if ($this->getMediaType()) {
            $config['CSSselectMedia'] = $this->getMediaType();
        }

        $config = $this->applyDocumentFormatConfiguration($config);

        $config = $this->applyFonts($config);

        if ($this->getBeforeCreateInstanceCallback()) {
            /** @var BeforeCreateLibraryInstanceCallback $callback */
            $callback = \call_user_func($this->getBeforeCreateInstanceCallback(), new BeforeCreateLibraryInstanceCallback(
                static::getType(), ['config' => $config]
            ));

            if ($callback && isset($callback->getConstructorParameters()['config'])) {
                $config = $callback->getConstructorParameters()['config'];
            }
        }

        $pdf = new Mpdf($config);

        if ($this->getLogger()) {
            $pdf->setLogger($this->getLogger());
        }

        $this->applyTemplate($pdf);

        if ($this->getHtmlContent()) {
            $pdf->WriteHTML($this->getHtmlContent());
        }

        $outputMode = '';
        $filename = $this->getFilename() ?: '';

        switch ($this->getOutputMode()) {
            case static::OUTPUT_MODE_STRING:
                $outputMode = Destination::STRING_RETURN;

                break;

            case static::OUTPUT_MODE_FILE:
                if (($folder = $this->getFolder()) && $this->getFilename()) {
                    $filename = rtrim($folder, '/').'/'.$filename;
                }

                $outputMode = Destination::FILE;

                break;

            case static::OUTPUT_MODE_DOWNLOAD:
                $outputMode = Destination::DOWNLOAD;

                break;

            case static::OUTPUT_MODE_INLINE:
                $outputMode = Destination::INLINE;

                break;
        }

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

        if (static::OUTPUT_MODE_STRING === $this->getOutputMode()) {
            $result->setFileContent($pdf->Output($filename, $outputMode));
        } else {
            if (static::OUTPUT_MODE_FILE === $this->getOutputMode()) {
                $result->setFilePath($filename);
            }
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

    public static function getType(): string
    {
        return 'mpdf';
    }

    /**
     * Add font directories to the config. Directory must contain mpdf-config.php.
     * Fallback method for legacy implementation, will be removed in a future version.
     *
     * @param array $paths Absolute path to font dir
     *
     * @return self Current pdf creator instance
     *
     * @deprecated Use addFont instead
     */
    public function addFontDirectories(array $paths): self
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        foreach ($paths as $fontDir) {
            if (!file_exists($fontDir) || !file_exists($fontDir.\DIRECTORY_SEPARATOR.'mpdf-config.php')) {
                continue;
            }

            $configPath = $fontDir.\DIRECTORY_SEPARATOR.'mpdf-config.php';
            $fontConfig = require_once $configPath;

            if (!\is_array($fontConfig)) {
                continue;
            }

            if (!isset($fontConfig['fontDir'])) {
                $fontConfig['fontDir'] = array_merge($fontDirs, [
                    $fontDir,
                ]);
            }

            $this->legacyFontDirectoryConfig = array_merge($this->legacyFontDirectoryConfig ?: [], $fontConfig);
        }

        return $this;
    }

    protected function applyFonts(array $config): array
    {
        if ($this->legacyFontDirectoryConfig) {
            $fontDirs = $this->legacyFontDirectoryConfig['fontDir'];
        } else {
            $defaultConfig = (new ConfigVariables())->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];
        }

        if ($this->legacyFontDirectoryConfig) {
            $fontData = $this->legacyFontDirectoryConfig['fontdata'];
        } else {
            $defaultFontConfig = (new FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];
        }

        if ($this->getFonts()) {
            $dirs = [];
            $families = [];
            $slugGenerator = new SlugGenerator(['delimiter' => '']);

            foreach ($this->getFonts() as $font) {
                $file = pathinfo($font['filepath']);
                $dirs[] = $file['dirname'];
                $fontStyle = 'R';

                if (static::FONT_STYLE_BOLD === $font['weight']) {
                    $fontStyle = 'B';
                } elseif (static::FONT_STYLE_ITALIC === $font['style']) {
                    $fontStyle = 'I';
                } elseif ((static::FONT_STYLE_BOLD === $font['weight']) && (static::FONT_STYLE_ITALIC === $font['style'])) {
                    $fontStyle = 'BI';
                }

                $families[$slugGenerator->generate($font['family'])][$fontStyle] = $file['basename'];
            }

            $fontDirs = array_merge($fontDirs, array_unique($dirs));
            $fontData = array_merge($fontData, $families);
        }

        $config['fontDir'] = $fontDirs;
        $config['fontdata'] = $fontData;

        return $config;
    }

    protected function applyDocumentFormatConfiguration(array $config): array
    {
        if ($this->getMargins()) {
            if ($this->getMargins()['top']) {
                $config['margin_top'] = $this->getMargins()['top'];
            }

            if ($this->getMargins()['right']) {
                $config['margin_right'] = $this->getMargins()['right'];
            }

            if ($this->getMargins()['bottom']) {
                $config['margin_bottom'] = $this->getMargins()['bottom'];
            }

            if ($this->getMargins()['left']) {
                $config['margin_left'] = $this->getMargins()['left'];
            }
        }

        if ($this->getOrientation()) {
            switch ($this->getOrientation()) {
                case static::ORIENTATION_PORTRAIT:
                    $config['orientation'] = 'P';

                    break;

                case static::ORIENTATION_LANDSCAPE:
                    $config['orientation'] = 'L';

                    break;
            }
        }

        if ($this->getFormat()) {
            if (\is_string($this->getFormat()) && static::ORIENTATION_LANDSCAPE === $this->getOrientation()) {
                $config['format'] = $this->getFormat().'-L';
            } else {
                $config['format'] = $this->getFormat();
            }
        }

        return $config;
    }

    /**
     * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
     */
    protected function applyTemplate(Mpdf $pdf): void
    {
        if ($this->getTemplateFilePath()) {
            if (file_exists($this->getTemplateFilePath())) {
                if (version_compare(Mpdf::VERSION, '8', '>')) {
                    $pageCount = $pdf->setSourceFile($this->getTemplateFilePath());
                    $tplIdx = $pdf->importPage($pageCount);
                    $pdf->useTemplate($tplIdx);
                } else {
                    // mpdf 7.x support
                    $pdf->SetImportUse();
                    $pageCount = $pdf->SetSourceFile($this->getTemplateFilePath());
                    $tplIdx = $pdf->ImportPage($pageCount);
                    $pdf->UseTemplate($tplIdx);
                }
            } else {
                trigger_error('Pdf template does not exist.', \E_USER_NOTICE);
            }
        }
    }
}
