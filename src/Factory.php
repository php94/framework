<?php

declare(strict_types=1);

namespace PHP94;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

class Factory
{
    public static function createRequest(string $method, $uri): RequestInterface
    {
        return Container::get(RequestFactoryInterface::class)->createRequest($method, $uri);
    }

    public static function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return Container::get(ResponseFactoryInterface::class)->createResponse($code, $reasonPhrase);
    }

    public static function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return Container::get(ServerRequestFactoryInterface::class)->createServerRequest($method, $uri, $serverParams);
    }

    public static function createStream(string $content = ''): StreamInterface
    {
        return Container::get(StreamFactoryInterface::class)->createStream($content);
    }

    public static function createStreamFromFile(string $file, string $mode = 'r'): StreamInterface
    {
        return Container::get(StreamFactoryInterface::class)->createStreamFromFile($file, $mode);
    }

    public static function createStreamFromResource($resource): StreamInterface
    {
        return Container::get(StreamFactoryInterface::class)->createStreamFromResource($resource);
    }

    public static function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = \UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface {
        return Container::get(UploadedFileFactoryInterface::class)->createUploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    public static function createUri(string $uri = ''): UriInterface
    {
        return Container::get(UriFactoryInterface::class)->createUri($uri);
    }
}
