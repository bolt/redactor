<?php

declare(strict_types=1);

namespace Bolt\Redactor\Controller;

use Bolt\Configuration\Config;
use Bolt\Controller\Backend\Async\AsyncZoneInterface;
use Bolt\Controller\CsrfTrait;
use Bolt\Redactor\RedactorConfig;
use Bolt\Twig\TextExtension;
use Bolt\Utils\ThumbnailHelper;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tightenco\Collect\Support\Collection;

#[IsGranted('list_files:files')]
class Images implements AsyncZoneInterface
{
    use CsrfTrait;

    public function __construct(
        private readonly Config $config,
        private readonly RequestStack $requestStack,
        private readonly ThumbnailHelper $thumbnailHelper,
        private readonly RedactorConfig $redactorConfig,
        CsrfTokenManagerInterface $csrfTokenManager,
    ) {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    #[Route('/redactor_images', name: 'bolt_redactor_images', methods: [Request::METHOD_GET])]
    public function getImagesList(Request $request): JsonResponse
    {
        try {
            $this->validateCsrf('bolt_redactor');
        } catch (InvalidCsrfTokenException) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Invalid CSRF token',
            ], Response::HTTP_FORBIDDEN);
        }

        $locationName = $this->requestStack->getCurrentRequest()->query->get('location', 'files');
        $path = $this->config->getPath($locationName, true);

        $files = $this->getImageFilesIndex($path);

        return new JsonResponse($files);
    }

    private function getImageFilesIndex(string $path): Collection
    {
        $glob = '*.{' . implode(',', self::getImageTypes()) . '}';

        $files = [];

        foreach ($this->findFiles($path, $glob) as $file) {
            $files[] = [
                'thumb' => $this->thumbnailHelper->path($file->getRelativePathname(), 400, 300, null, null, 'crop'),
                'url' => $thumbnail = '/thumbs/' . $this->redactorConfig->getConfig()['image']['thumbnail'] . '/' . $file->getRelativePathname(),
            ];
        }

        return new Collection($files);
    }

    #[Route('/redactor_files', name: 'bolt_redactor_files', methods: [Request::METHOD_GET])]
    public function getFilesList(Request $request): JsonResponse
    {
        try {
            $this->validateCsrf('bolt_redactor');
        } catch (InvalidCsrfTokenException) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Invalid CSRF token',
            ], Response::HTTP_FORBIDDEN);
        }

        $locationName = $this->requestStack->getCurrentRequest()->query->get('location', 'files');
        $path = $this->config->getPath($locationName, true);

        $files = $this->getFilesIndex($path);

        return new JsonResponse($files);
    }

    private function getFilesIndex(string $path): Collection
    {
        $glob = '*.{' . implode(',', self::getFileTypes()) . '}';

        $files = [];

        $textExtenion = new TextExtension();

        foreach ($this->findFiles($path, $glob) as $file) {
            $files[] = [
                'title' => $file->getRelativePathname(),
                'url' => '/files/' . $file->getRelativePathname(),
                'size' => $textExtenion->formatBytes($file->getSize(), 1),
            ];
        }

        return new Collection($files);
    }

    private function findFiles(string $path, ?string $glob = null): Finder
    {
        $finder = new Finder();
        $finder->in($path)->depth('< 3')->sortByType()->files();

        if ($glob) {
            $finder->name($glob);
        }

        return $finder;
    }

    private static function getImageTypes(): array
    {
        return ['gif', 'png', 'jpg', 'jpeg', 'svg', 'avif', 'webp'];
    }

    private static function getFileTypes(): array
    {
        return ['doc', 'docx', 'txt', 'pdf', 'xls', 'xlsx', 'zip', 'tgz', 'gz'];
    }
}
