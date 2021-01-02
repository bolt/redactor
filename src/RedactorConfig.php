<?php

declare(strict_types=1);

namespace Bolt\Redactor;

use Bolt\Configuration\Config;
use Bolt\Entity\Content;
use Bolt\Extension\ExtensionRegistry;
use Bolt\Storage\Query;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RedactorConfig
{
    private const CACHE_DURATION = 1800; // 30 minutes

    /** @var ExtensionRegistry */
    private $registry;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var CsrfTokenManagerInterface */
    private $csrfTokenManager;

    /** @var Config */
    private $boltConfig;

    /** @var Query */
    private $query;

    /** @var array */
    private $config = null;

    /** @var array */
    private $plugins = null;

    /** @var CacheInterface */
    private $cache;

    public function __construct(
        ExtensionRegistry $registry,
        UrlGeneratorInterface $urlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager,
        Config $boltConfig,
        Query $query,
        CacheInterface $cache
    ) {
        $this->registry = $registry;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->boltConfig = $boltConfig;
        $this->query = $query;
        $this->cache = $cache;
    }

    public function getConfig(): array
    {
        if ($this->config) {
            return $this->config;
        }

        $extension = $this->registry->getExtension(Extension::class);

        $this->config = array_replace_recursive($this->getDefaults(), $extension->getConfig()['default'], $this->getLinks());

        return $this->config;
    }

    public function getPlugins(): array
    {
        if ($this->plugins) {
            return $this->plugins;
        }

        $extension = $this->registry->getExtension(Extension::class);

        $this->plugins = $this->getDefaultPlugins();

        if (is_array($extension->getConfig()['plugins'])) {
            $this->plugins = array_replace_recursive($this->plugins, $extension->getConfig()['plugins']);
        }

        return $this->plugins;
    }

    public function getDefaults()
    {
        return [
            'image' => [
                'thumbnail' => '1000×1000×max',
            ],
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

    private function getLinks(): array
    {
        return $this->cache->get('editor_insert_links', function (ItemInterface $item) {
            $item->expiresAfter(self::CACHE_DURATION);

            return $this->getLinksHelper();
        });
    }

    private function getLinksHelper(): array
    {
        $amount = 100;
        $params = [
            'status' => 'published',
            'returnmultiple' => true,
            'order' => '-modifiedAt',
        ];
        $contentTypes = $this->boltConfig->get('contenttypes')->where('viewless', false)->keys()->implode(',');

        $records = $this->query->getContentForTwig($contentTypes, $params)->setMaxPerPage($amount);

        $links = [
            '___' => [
                'name' => '(Choose an existing Record)',
                'url' => '',
            ],
        ];

        /** @var Content $record */
        foreach ($records as $record) {
            $extras = $record->getExtras();

            $links[$extras['title']] = [
                'name' => sprintf('%s [%s № %s]', $extras['title'], $extras['name'], $record->getId()),
                'url' => $extras['link'],
            ];
        }

        ksort($links, SORT_STRING | SORT_FLAG_CASE);

        return [
            'definedlinks' => array_values($links),
        ];
    }
}
