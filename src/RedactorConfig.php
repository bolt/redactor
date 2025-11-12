<?php

declare(strict_types=1);

namespace Bolt\Redactor;

use Bolt\Configuration\Config;
use Bolt\Entity\Content;
use Bolt\Extension\ExtensionRegistry;
use Bolt\Storage\Query;
use Pagerfanta\PagerfantaInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RedactorConfig
{
    private const CACHE_DURATION = 1800; // 30 minutes

    /** @var array<string, null|bool|string|array<string, array<string, bool|string>|string>> */
    private ?array $config = null;

    /** @var array<string, string[]> */
    private ?array $plugins = null;

    public function __construct(
        private readonly ExtensionRegistry $registry,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly Config $boltConfig,
        private readonly Query $query,
        private readonly CacheInterface $cache,
        private readonly Security $security
    ) {
    }

    /**
     * @phpstan-ignore missingType.iterableValue (complex type)
     */
    public function getConfig(): array
    {
        if ($this->config) {
            return $this->config;
        }

        $extension = $this->getExtension();

        $this->config = array_replace_recursive($this->getDefaults(), $extension->getConfig()['default'], $this->getLinks());

        return $this->config;
    }

    /**
     * @phpstan-ignore missingType.iterableValue (complex type)
     */
    public function getPlugins(): array
    {
        if ($this->plugins) {
            return $this->plugins;
        }

        $extension = $this->getExtension();

        $this->plugins = $this->getDefaultPlugins();

        if (isset($extension->getConfig()['plugins']) && is_array($extension->getConfig()['plugins'])) {
            $this->plugins = array_replace_recursive($this->plugins, $extension->getConfig()['plugins']);
        }

        return $this->plugins;
    }

    /**
     * @return array<string, null|bool|string|array<string, array<string, bool|string>|string>>
     */
    public function getDefaults(): array
    {
        $defaults = [
            'image' => [
                'thumbnail' => '1000×1000×max',
            ],
            'imageUpload' => $this->urlGenerator->generate('bolt_redactor_upload', ['location' => 'files']),
            'imageManagerJson' => $this->urlGenerator->generate('bolt_redactor_images', [
                '_csrf_token' => $this->csrfTokenManager->getToken('bolt_redactor')->getValue(),
                'foo' => '1', // To ensure token is cut off correctly
            ]),
            'fileUpload' => $this->urlGenerator->generate('bolt_redactor_upload', [
                'location' => 'files',
                '_csrf_token' => $this->csrfTokenManager->getToken('bolt_redactor')->getValue(),
            ]),
            'fileManagerJson' => $this->urlGenerator->generate('bolt_redactor_files', [
                '_csrf_token' => $this->csrfTokenManager->getToken('bolt_redactor')->getValue(),
                'foo' => '1', // To ensure token is cut off correctly
            ]),
            'imageUploadParam' => 'file',
            'multipleUpload' => 'false',
            'imageData' => [
                '_csrf_token' => $this->csrfTokenManager->getToken('bolt_redactor')->getValue(),
            ],
            'minHeight' => '200px',
            'maxHeight' => '700px',
            'structure' => false,
            'pasteClean' => true,
            'source' => [
                'codemirror' => [
                    'lineNumbers' => true,
                    'lineWrapping' => true,
                    'mode' => 'text/html',
                    'matchBrackets' => true,
                ],
            ],
            'buttonsTextLabeled' => false,
            'includes' => [],
        ];

        if (! $this->security->isGranted('upload')) {
            $defaults['imageUpload'] = null;
        }

        if (! $this->security->isGranted('list_files:files')) {
            $defaults['imageManagerJson'] = null;
        }

        return $defaults;
    }

    /**
     * @return array<string, string[]>
     */
    public function getDefaultPlugins(): array
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

    /**
     * @phpstan-ignore missingType.iterableValue (complex type)
     */
    private function getLinks(): array
    {
        return $this->cache->get('redactor_insert_links', function (ItemInterface $item): array {
            $item->expiresAfter(self::CACHE_DURATION);

            return $this->getLinksHelper();
        });
    }

    /**
     * @phpstan-ignore missingType.iterableValue (complex type)
     */
    private function getLinksHelper(): array
    {
        $amount = 100;
        $params = [
            'status' => 'published',
            'returnmultiple' => true,
            'order' => '-modifiedAt',
        ];
        $contentTypes = $this->boltConfig->get('contenttypes')->where('viewless', false)->keys()->implode(',');

        /** @var Content[]|PagerfantaInterface<Content> $records */
        $records = $this->query->getContentForTwig($contentTypes, $params) ?? [];
        if ($records instanceof PagerfantaInterface) {
            $records->setMaxPerPage($amount);
        }

        $links = [
            '___' => [
                'name' => '(Choose an existing Record)',
                'url' => '',
            ],
        ];

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

    private function getExtension(): Extension
    {
        /** @var Extension|null $extension */
        $extension = $this->registry->getExtension(Extension::class);

        return $extension ?? throw new RuntimeException('Redactor extension not registered');
    }
}
