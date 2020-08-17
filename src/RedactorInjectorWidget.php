<?php

declare(strict_types=1);

namespace Bolt\Redactor;

use Bolt\Widget\BaseWidget;
use Bolt\Widget\Injector\RequestZone;
use Bolt\Widget\Injector\Target;
use Bolt\Widget\TwigAwareInterface;

class RedactorInjectorWidget extends BaseWidget implements TwigAwareInterface
{
    protected $name = 'Redactor Injector Widget';
    protected $target = Target::AFTER_JS;
    protected $zone = RequestZone::BACKEND;
    protected $template = '@redactor/injector.html.twig';
    protected $priority = 200;

    public function __construct()
    {
    }
}
