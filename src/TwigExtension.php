<?php

declare(strict_types=1);

namespace Bolt\Redactor;

use Bolt\Common\Json;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Webmozart\PathUtil\Path;

class TwigExtension extends AbstractExtension
{
    /** @var RedactorConfig */
    private $redactorConfig;

    public function __construct(RedactorConfig $redactorConfig)
    {
        $this->redactorConfig = $redactorConfig;
    }

    public function getFunctions(): array
    {
        $safe = [
            'is_safe' => ['html'],
        ];

        return [
            new TwigFunction('redactor_settings', [$this, 'redactorSettings'], $safe),
            new TwigFunction('redactor_includes', [$this, 'redactorIncludes'], $safe),
        ];
    }

    public function redactorSettings(): string
    {
        $settings = $this->redactorConfig->getConfig();

        return Json::json_encode($settings, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_PRETTY_PRINT);
    }


    public function redactorIncludes(): string
    {
        $used = $this->redactorConfig->getConfig()['plugins'];
        $plugins = collect($this->redactorConfig->getPlugins());

        $output = '';

        foreach ($used as $item) {
            if (! is_string($item) || ! $plugins->get($item)) {
                continue;
            }

            foreach ($plugins->get($item) as $file) {
                if (Path::getExtension($file) === 'css') {
                    $output .= sprintf('<link rel="stylesheet" href="/assets/redactor/_plugins/%s">', $file);
                }
                if (Path::getExtension($file) === 'js') {
                    $output .= sprintf('<script src="/assets/redactor/_plugins/%s"></script>', $file);
                }
                $output .= "\n";
            }
        }

        return $output;
    }
}
