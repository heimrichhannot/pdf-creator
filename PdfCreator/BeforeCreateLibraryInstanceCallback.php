<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\PdfCreator;

class BeforeCreateLibraryInstanceCallback
{
    /**
     * @var array
     */
    protected $constructorParameters;
    /**
     * @var string
     */
    protected $type;

    /**
     * BeforeCreateLibraryInstanceCallback constructor.
     */
    public function __construct(string $type, array $constructorParameters = [])
    {
        $this->constructorParameters = $constructorParameters;
        $this->type = $type;
    }

    public function getConstructorParameters(): array
    {
        return $this->constructorParameters;
    }

    public function setConstructorParameters(array $constructorParameters): void
    {
        $this->constructorParameters = $constructorParameters;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
