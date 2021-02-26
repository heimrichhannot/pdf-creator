<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\PdfCreator\Exception;

use Throwable;

class MissingDependenciesException extends \Exception
{
    /**
     * @var string
     */
    protected $type;
    /**
     * @var array
     */
    protected $dependencies;

    public function __construct(string $type, array $dependencies = [], $message = '', $code = 0, Throwable $previous = null)
    {
        if (empty($message)) {
            $message = 'There are missing dependencies the %type% pdf creator type needs to work. %dependencies%';
        }

        $message = str_replace('%type%', $type, $message);

        if (!empty($dependencies)) {
            $message = str_replace('%dependencies%', "\nMissing dependencies:\n".implode("\n", $dependencies), $message);
        } else {
            $message = trim(str_replace('%dependencies%', '', $message));
        }

        parent::__construct($message, $code, $previous);
        $this->type = $type;
        $this->dependencies = $dependencies;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }
}
