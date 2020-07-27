<?php


namespace Bolt\Redactor;

use Bolt\Common\Json;
use Bolt\Extension\ExtensionRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    /** @var ExtensionRegistry */
    private $registry;

    public function __construct(ExtensionRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function getFunctions(): array
    {
        $safe = [
            'is_safe' => ['html'],
        ];

        return [
            new TwigFunction('redactor_settings', [$this, 'redactorSettings'], $safe),
        ];
    }

    public function redactorSettings(): string
    {
        $extension = $this->registry->getExtension(Extension::class);
        $config = $extension->getConfig();

        return Json::json_encode($config['default'], JSON_HEX_QUOT|JSON_HEX_APOS);
    }
}