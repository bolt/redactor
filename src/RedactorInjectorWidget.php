<?php


namespace Bolt\Redactor;


use Bolt\Widget\BaseWidget;
use Bolt\Widget\Injector\RequestZone;
use Bolt\Widget\Injector\Target;
use Bolt\Widget\TwigAwareInterface;
use Webmozart\PathUtil\Path;

class RedactorInjectorWidget extends BaseWidget implements TwigAwareInterface
{
    protected $name = 'Redactor Injector Widget';
    protected $target = Target::AFTER_JS;
    protected $zone = RequestZone::BACKEND;
    protected $template = '@redactor/injector.html.twig';
    protected $priority = 200;

    /** @var Canonical */
    private $canonical;

    public function __construct()
    {
    }

    protected function run(array $params = []): ?string
    {
        $additional = $this->getAdditionalIncludes();

        return parent::run(['additional_includes' => $additional]);
    }

    private function getAdditionalIncludes()
    {
        $config = $this->getExtension()->getConfig();

        $used = collect($config->get('default'))->flatten();
        $plugins = collect($config->get('plugins'));

        $output = '';

        foreach($used as $item) {
            if (!is_string($item) || ! $plugins->get($item)) {
                continue;
            }

            foreach ($plugins->get($item) as $file) {
                if (Path::getExtension($file) == 'css') {
                    $output .= sprintf('<link rel="stylesheet" href="/assets/redactor-plugins/%s">', $file);
                }
                if (Path::getExtension($file) == 'js') {
                    $output .= sprintf('<script src="/assets/redactor-plugins/%s"></script>', $file);
                }
            }
        }

        return $output;
    }
}