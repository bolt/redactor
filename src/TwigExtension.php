<?php

declare(strict_types=1);

namespace Bolt\Redactor;

use Bolt\Common\Json;
use Bolt\Configuration\Config;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Path;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    /** @var RedactorConfig */
    private $redactorConfig;

    /** @var Config */
    private $boltConfig;

    /** @var Container */
    private $container;

    public function __construct(RedactorConfig $redactorConfig, Config $boltConfig, ContainerInterface $container)
    {
        $this->redactorConfig = $redactorConfig;
        $this->boltConfig = $boltConfig;
        $this->container = $container;
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
        // First, the includes needed for the various activated plugins
        $used = $this->redactorConfig->getConfig()['plugins'];
        $plugins = collect($this->redactorConfig->getPlugins());

        $output = '';

        foreach ($used as $item) {
            if (! is_string($item) || ! $plugins->get($item)) {
                continue;
            }

            foreach ($plugins->get($item) as $file) {
                if (Path::getExtension($file) === 'css') {
                    $output .= sprintf('<link rel="stylesheet" href="/assets/redactor/plugins/%s">', $file);
                }
                if (Path::getExtension($file) === 'js') {
                    $output .= sprintf('<script src="/assets/redactor/plugins/%s"></script>', $file);
                }
                $output .= "\n";
            }
        }

        // Next, if there are extra inludes configured, we add them here
        $includes = $this->redactorConfig->getConfig()['includes'];

        foreach ($includes as $item) {
            $item = $this->makePath($item);

            if (Path::getExtension($item) === 'css') {
                $output .= sprintf('<link rel="stylesheet" href="%s">', $item);
            }
            if (Path::getExtension($item) === 'js') {
                $output .= sprintf('<script src="%s"></script>', $item);
            }
            $output .= "\n";
        }

        return $output;
    }

    private function makePath(string $item): string
    {
        $path = $this->boltConfig->getPath($item, false);
        $publicFolder = $this->container->getParameter('kernel.project_dir') . '/' . $this->container->getParameter('bolt.public_folder');

        $path = '/' . Path::makeRelative($path, $publicFolder);

        return $path;
    }
}
