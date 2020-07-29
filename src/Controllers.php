<?php

declare(strict_types=1);

namespace Bolt\Redactor;

use Bolt\Configuration\Config;
use Bolt\Controller\Backend\Async\AsyncZoneInterface;
use Bolt\Controller\CsrfTrait;
use Bolt\Twig\TextExtension;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sirius\Upload\Handler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @Security("is_granted('ROLE_ADMIN')")
 */
class Controllers implements AsyncZoneInterface
{
    use CsrfTrait;

    /** @var Config */
    private $config;

    /** @var TextExtension */
    private $textExtension;

    /** @var Request */
    private $request;

    public function __construct(Config $config, CsrfTokenManagerInterface $csrfTokenManager, TextExtension $textExtension, RequestStack $requestStack)
    {
        $this->config = $config;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->textExtension = $textExtension;
        $this->request = $requestStack->getCurrentRequest();
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/redactor_upload", name="bolt_redactor_upload", methods={"POST"})
     */
    public function handleUpload(Request $request): JsonResponse
    {
        try {
            $this->validateCsrf('bolt_redactor');
        } catch (InvalidCsrfTokenException $e) {
            return new JsonResponse([
                'error' => [
                    'message' => 'Invalid CSRF token',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        $locationName = $this->request->query->get('location', '');
        $path = $this->request->query->get('path', '');

        $target = $this->config->getPath($locationName, true, $path);

        $uploadHandler = new Handler($target, [
            Handler::OPTION_AUTOCONFIRM => true,
            Handler::OPTION_OVERWRITE => true,
        ]);

        $acceptedFileTypes = array_merge($this->config->getMediaTypes()->toArray(), $this->config->getFileTypes()->toArray());
        $maxSize = $this->config->getMaxUpload();

        $uploadHandler->addRule(
            'extension',
            [
                'allowed' => $acceptedFileTypes,
            ],
            'The file for field \'{label}\' was <u>not</u> uploaded. It should be a valid file type. Allowed are <code>' . implode('</code>, <code>', $acceptedFileTypes) . '.',
            'Upload file'
        );

        $uploadHandler->addRule(
            'size',
            ['size' => $maxSize],
            'The file for field \'{label}\' was <u>not</u> uploaded. The upload can have a maximum filesize of <b>' . $this->textExtension->formatBytes($maxSize) . '</b>.',
            'Upload file'
        );

        $uploadHandler->setSanitizerCallback(function ($name) {
            return $this->sanitiseFilename($name);
        });

        try {
            $files = current($request->files->all());

            if (is_array($files)) {
                $files = current($files);
            }

            /** @var File $result */
            $result = $uploadHandler->process(['image' => $files]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => [
                    'message' => $e->getMessage() . ' Ensure the upload does <em><u>not</u></em> exceed the maximum filesize of <b>' . $this->textExtension->formatBytes($maxSize) . '</b>, and that the destination folder (on the webserver) is writable.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($result->isValid()) {
            try {
                $media = $this->mediaFactory->createFromFilename($locationName, $path, $result->__get('name'));
                $this->em->persist($media);
                $this->em->flush();

                return new JsonResponse($media->getFilenamePath());
            } catch (\Throwable $e) {
                // something wrong happened, we don't need the uploaded files anymore
                $result->clear();

                throw $e;
            }
        }

        // image was not moved to the container, where are error messages
        $messages = $result->getMessages();

        return new JsonResponse([
            'error' => [
                'message' => implode(', ', $messages),
            ],
        ], Response::HTTP_BAD_REQUEST);
    }

    private function sanitiseFilename(string $filename): string
    {
        $extensionSlug = new Slugify(['regexp' => '/([^a-z0-9]|-)+/']);
        $filenameSlug = new Slugify(['lowercase' => false]);

        $extension = $extensionSlug->slugify(Path::getExtension($filename));
        $filename = $filenameSlug->slugify(Path::getFilenameWithoutExtension($filename));

        return $filename . '.' . $extension;
    }
}
