<?php

declare(strict_types=1);

namespace Bolt\Redactor;

use Bolt\Entity\Field;
use Bolt\Entity\Field\Excerptable;
use Bolt\Entity\FieldInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class RedactorField extends Field implements Excerptable, FieldInterface
{
    public const TYPE = 'redactor';
}
