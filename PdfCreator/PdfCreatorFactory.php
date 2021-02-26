<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\PdfCreator;

use HeimrichHannot\PdfCreator\Concrete\MpdfCreator;
use HeimrichHannot\PdfCreator\Concrete\TcpdfCreator;

class PdfCreatorFactory
{
    protected static $types;

    /**
     * Return supported pdf creator types.
     *
     * @return array
     */
    public static function getTypes()
    {
        return array_keys(static::getPdfCreatorRegistry());
    }

    /**
     * Return a pdf creator instance for given type or null, if no type is registered for given type.
     */
    public static function createInstance(string $type): ?AbstractPdfCreator
    {
        $types = static::getPdfCreatorRegistry();

        if (isset($types[$type])) {
            return new $types[$type]();
        }

        return null;
    }

    public static function addType(AbstractPdfCreator $type)
    {
        static::getPdfCreatorRegistry();
        static::$types[$type::getType()] = \get_class($type);
    }

    protected static function getPdfCreatorRegistry()
    {
        if (!static::$types) {
            static::$types = [
                MpdfCreator::getType() => MpdfCreator::class,
                TcpdfCreator::getType() => TcpdfCreator::class,
            ];
        }

        return static::$types;
    }
}
