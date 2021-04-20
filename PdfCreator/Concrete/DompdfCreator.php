<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\PdfCreator\Concrete;

use Dompdf\Adapter\CPDF;
use Dompdf\Dompdf;
use Dompdf\Options;
use HeimrichHannot\PdfCreator\AbstractPdfCreator;
use HeimrichHannot\PdfCreator\BeforeCreateLibraryInstanceCallback;
use HeimrichHannot\PdfCreator\BeforeOutputPdfCallback;
use HeimrichHannot\PdfCreator\Exception\MissingDependenciesException;

class DompdfCreator extends AbstractPdfCreator
{
    public static function getType(): string
    {
        return 'dompdf';
    }

    public static function isUsable(bool $triggerExeption = false): bool
    {
        if (!class_exists('Dompdf\Dompdf')) {
            if ($triggerExeption) {
                throw new MissingDependenciesException(static::getType(), ['"dompdf/dompdf": "^1.0"']);
            }

            return false;
        }

        return true;
    }

    public function render()
    {
        static::isUsable(true);

        $options = new Options();
        $options->setIsRemoteEnabled(true);

        if ($this->getBeforeCreateInstanceCallback()) {
            /** @var BeforeCreateLibraryInstanceCallback $result */
            $result = \call_user_func(
                $this->getBeforeCreateInstanceCallback(),
                new BeforeCreateLibraryInstanceCallback(static::getType(), ['options' => $options])
            );

            if ($result && isset($result->getConstructorParameters()['options'])) {
                $options = $result->getConstructorParameters()['options'];
            }
        }

        $dompdf = new Dompdf($options);

        if ($this->getHtmlContent()) {
            $dompdf->loadHtml($this->getHtmlContent());
        }

        $orientation = static::ORIENTATION_PORTRAIT;

        if ($this->getOrientation()) {
            switch ($this->getOrientation()) {
                case static::ORIENTATION_LANDSCAPE:
                    $orientation = $this->getOrientation();

                    break;
            }
        }

        $format = 'A4';

        if ($this->getFormat()) {
            if (\is_string($this->getFormat()) && isset(CPDF::$PAPER_SIZES[mb_strtolower($this->getFormat())])) {
                $format = $this->getFormat();
            } elseif (\is_array($this->getFormat())) {
                $width = ($this->getFormat()[0] / 25.4) * 72;
                $height = ($this->getFormat()[1] / 25.4) * 72;
                $format = [0, 0, $width, $height];
            }
        }

        $dompdf->setPaper($format, $orientation);

        $dompdf->render();

        $filename = $this->getFilename();
        $options = [];

        switch ($this->getOutputMode()) {
            case static::OUTPUT_MODE_DOWNLOAD:
                $options = ['Attachment' => 1];

                break;

            case static::OUTPUT_MODE_INLINE:
                $options = ['Attachment' => 0];

                break;
        }

        if ($this->getBeforeOutputPdfCallback()) {
            /** @var BeforeOutputPdfCallback $result */
            $result = \call_user_func($this->getBeforeOutputPdfCallback(), new BeforeOutputPdfCallback(static::getType(), $dompdf, [
                'filename' => $filename,
                'options' => $options,
            ]));

            if ($result) {
                if (isset($result->getOutputParameters()['filename']) && \is_string($result->getOutputParameters()['filename'])) {
                    $filename = $result->getOutputParameters()['filename'];
                }

                if (isset($result->getOutputParameters()['options']) && \is_array($result->getOutputParameters()['options'])) {
                    $options = $result->getOutputParameters()['options'];
                }
            }
        }

        switch ($this->getOutputMode()) {
            case static::OUTPUT_MODE_DOWNLOAD:
            case static::OUTPUT_MODE_INLINE:
                $dompdf->stream($filename, $options);

                exit;

            case static::OUTPUT_MODE_STRING:
                return $dompdf->output();

            case static::OUTPUT_MODE_FILE:
                // @ToDo (https://ourcodeworld.com/articles/read/799/how-to-create-a-pdf-from-html-in-symfony-4-using-dompdf)
        }
    }

    public function getSupportedOutputModes(): array
    {
        return [
            self::OUTPUT_MODE_INLINE,
            self::OUTPUT_MODE_DOWNLOAD,
            self::OUTPUT_MODE_STRING,
        ];
    }

    public function supports(): array
    {
        return [];
    }
}
