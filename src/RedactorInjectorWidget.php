<?php

declare(strict_types=1);

namespace Bolt\Redactor;

use Bolt\Widget\BaseWidget;
use Bolt\Widget\Injector\RequestZone;
use Bolt\Widget\Injector\Target;
use Bolt\Widget\TwigAwareInterface;
use Symfony\Component\HttpFoundation\Request;

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

    /**
     * @phpstan-ignore missingType.iterableValue (not used here)
     */
    public function run(array $params = []): ?string
    {
        $request = $this->getExtension()->getRequest();
        // Only produce output when editing or creating a Record, with GET method.
        if (! in_array($request->get('_route'), ['bolt_content_edit', 'bolt_content_new', 'bolt_content_duplicate'], true) ||
            ($this->getExtension()->getRequest()->getMethod() !== Request::METHOD_GET)) {
            return null;
        }

        return parent::run();
    }
}
