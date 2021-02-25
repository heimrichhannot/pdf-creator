<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\PdfCreator;

class BeforeOutputPdfCallback
{
    protected $libraryInstance;
    /**
     * @var array
     */
    protected $outputParameters;
    /**
     * @var string
     */
    protected $type;

    public function __construct(string $type, $libraryInstance, array $outputParameters = [])
    {
        $this->libraryInstance = $libraryInstance;
        $this->outputParameters = $outputParameters;
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getLibraryInstance()
    {
        return $this->libraryInstance;
    }

    /**
     * @param mixed $libraryInstance
     */
    public function setLibraryInstance($libraryInstance): void
    {
        $this->libraryInstance = $libraryInstance;
    }

    public function getOutputParameters(): array
    {
        return $this->outputParameters;
    }

    public function setOutputParameters(array $outputParameters): void
    {
        $this->outputParameters = $outputParameters;
    }
}
