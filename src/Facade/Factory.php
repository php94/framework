<?php

declare(strict_types=1);

namespace PHP94\Facade;

use PHP94\Factory\Factory as FactoryFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

class Factory
{
    public static function getInstance(): FactoryFactory
    {
        return Container::get(FactoryFactory::class);
    }

    public static function createRequest(string $method, $uri): RequestInterface
    {
        return self::getInstance()->createRequest($method, $uri);
    }

    public static function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return self::getInstance()->createResponse($code, $reasonPhrase);
    }

    public static function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return self::getInstance()->createServerRequest($method, $uri, $serverParams);
    }

    public static function createStream(string $content = ''): StreamInterface
    {
        return self::getInstance()->createStream($content);
    }

    public static function createStreamFromFile(string $file, string $mode = 'r'): StreamInterface
    {
        return self::getInstance()->createStreamFromFile($file, $mode);
    }

    public static function createStreamFromResource($resource): StreamInterface
    {
        return self::getInstance()->createStreamFromResource($resource);
    }

    public static function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = \UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface {
        return self::getInstance()->createUploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    public static function createUri(string $uri = ''): UriInterface
    {
        return self::getInstance()->createUri($uri);
    }
}
