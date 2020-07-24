<?php

declare(strict_types=1);

namespace Bolt\Redactor;

use Bolt\Extension\BaseExtension;

class Extension extends BaseExtension
{
    public function getName(): string
    {
        return 'Bolt Extension to add the Redactor FieldType';
    }

    public function initialize(): void
    {
    }

    public function install(): void
    {

    }
}
