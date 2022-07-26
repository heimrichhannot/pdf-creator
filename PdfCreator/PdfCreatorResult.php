<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\PdfCreator;

class PdfCreatorResult
{
    private string $outputMode;
    private string $filePath;
    private string $fileContent;

    public function __construct(string $outputMode)
    {
        $this->outputMode = $outputMode;
    }

    public function getOutputMode(): string
    {
        return $this->outputMode;
    }

    public function setOutputMode(string $outputMode): self
    {
        $this->outputMode = $outputMode;

        return $this;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getFileContent(): string
    {
        return $this->fileContent;
    }

    public function setFileContent(string $fileContent): self
    {
        $this->fileContent = $fileContent;

        return $this;
    }
}
