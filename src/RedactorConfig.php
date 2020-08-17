<?php

declare(strict_types=1);

namespace Bolt\Redactor;

use Bolt\Extension\ExtensionRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class RedactorConfig
{
    /** @var ExtensionRegistry */
    private $registry;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var CsrfTokenManagerInterface */
    private $csrfTokenManager;

    public function __construct(ExtensionRegistry $registry, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->registry = $registry;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public function getConfig(): array
    {
        $extension = $this->registry->getExtension(Extension::class);

        return array_merge($this->getDefaults(), $extension->getConfig()['default']);
    }

    public function getPlugins(): array
    {
        $extension = $this->registry->getExtension(Extension::class);

        $plugins = $this->getDefaultPlugins();

        if (is_array($extension->getConfig()['plugins'])) {
            $plugins = array_merge($plugins, $extension->getConfig()['plugins']);
        }

        return $plugins;
    }

    public function getDefaults()
    {
        return [
            'imageUpload' => $this->urlGenerator->generate('bolt_redactor_upload', ['location' => 'files']),
            'imageUploadParam' => 'file',
            'multipleUpload' => 'false',
            'imageData' => [
                '_csrf_token' => $this->csrfTokenManager->getToken('bolt_redactor')->getValue(),
            ],
            'minHeight' => '200px',
            'maxHeight' => '700px',
            'structure' => true,
            'pasteClean' => true,
            'source' => [
                'codemirror' => [
                    'lineNumbers' => true,
                ],
            ],
            'buttonsTextLabeled' => false,
        ];
    }

    public function getDefaultPlugins()
    {
        return [
            'alignment' => ['alignment/alignment.min.js'],
            'beyondgrammar' => ['beyondgrammar/beyondgrammar.min.js'],
            'clips' => ['clips/clips.min.js', 'clips/clips.min.css'],
            'counter' => ['counter/counter.min.js'],
            'definedlinks' => ['definedlinks/definedlinks.min.js'],
            'filemanager' => ['filemanager/filemanager.min.js', 'filemanager/filemanager.min.css'],
            'fontcolor' => ['fontcolor/fontcolor.min.js'],
            'fontfamily' => ['fontfamily/fontfamily.min.js'],
            'fontsize' => ['fontsize/fontsize.min.js'],
            'fullscreen' => ['fullscreen/fullscreen.min.js'],
            'handle' => ['handle/handle.min.js', 'handle/handle.min.css'],
            'imagemanager' => ['imagemanager/imagemanager.min.js'],
            'inlinestyle' => ['inlinestyle/inlinestyle.min.js', 'inlinestyle/inlinestyle.min.css'],
            'limiter' => ['limiter/limiter.min.js'],
            'pagebreak' => ['pagebreak/pagebreak.min.js', 'pagebreak/pagebreak.min.css'],
            'properties' => ['properties/properties.min.js'],
            'specialchars' => ['specialchars/specialchars.min.js'],
            'table' => ['table/table.min.js'],
            'textdirection' => ['textdirection/textdirection.min.js'],
            'textexpander' => ['textexpander/textexpander.min.js'],
            'variable' => ['variable/variable.min.js', 'variable/variable.min.css'],
            'video' => ['video/video.min.js'],
            'widget' => ['widget/widget.min.js'],
        ];
    }
}
