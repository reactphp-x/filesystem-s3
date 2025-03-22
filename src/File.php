<?php

namespace ReactphpX\FilesystemS3;

use React\Filesystem\Node\FileInterface;
use React\Filesystem\Node\NodeInterface;
use React\Filesystem\Stat;
use React\Promise\PromiseInterface;
use React\Promise\Promise;
use React\EventLoop\Loop;
use React\Filesystem\PollInterface;

final class File implements FileInterface
{
    private Adapter $adapter;
    private string $directory;
    private string $name;
    private PollInterface $poll;



    public function __construct(Adapter $adapter, string $directory, string $name)
    {
        $this->poll = $adapter->getPoll();
        $this->adapter = $adapter;
        $this->directory = $directory;
        $this->name = $name;
    }

    public function path(): string
    {
        return $this->directory . $this->name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function stat(): PromiseInterface
    {
        throw new \RuntimeException("Not implemented");
    }

    public function unlink(): PromiseInterface
    {
        $this->activate();
        return new Promise(function ($resolve, $reject) {
            $this->adapter->getS3Client()->deleteObjectAsync([
                'Bucket' => $this->adapter->getBucket(),
                'Key' => $this->path(),
            ])->then(
                function () use ($resolve) {
                    $this->deactivate();
                    $resolve(true);
                },
                function ($error) use ($reject) {
                    $this->deactivate();
                    $reject($error);
                }
            );
        });
    }

    public function getContents(int $offset = 0, ?int $maxlen = null): PromiseInterface
    {
        $this->activate();
        return new Promise(function ($resolve, $reject) use ($offset, $maxlen) {
            $params = [
                'Bucket' => $this->adapter->getBucket(),
                'Key' => $this->path(),
            ];

            if ($offset > 0 || $maxlen !== null) {
                $range = 'bytes=' . $offset . '-';
                if ($maxlen !== null) {
                    $range .= ($offset + $maxlen - 1);
                }
                $params['Range'] = $range;
            }

            $this->adapter->getS3Client()->getObjectAsync($params)->then(
                function ($result) use ($resolve) {
                    $this->deactivate();
                    $resolve((string) $result['Body']);
                },
                function ($error) use ($reject) {
                    $this->deactivate();
                    $reject($error);
                }
            );
        });
    }

    protected function activate(): void
    {
        $this->poll->activate();
    }

    protected function deactivate(): void
    {
        $this->poll->deactivate();
    }


    // todo putStream
    public function putContents(string $contents, int $flags = 0): PromiseInterface
    {
        $this->activate();
        return new Promise(function ($resolve, $reject) use ($contents) {
            try {
                $length = strlen($contents);
                $extension = strtolower(pathinfo($this->path(), PATHINFO_EXTENSION)); // Convert extension to lowercase
                $mimeType = self::$mime_types[$extension] ?? 'application/octet-stream'; // Fallback to default MIME type
                $this->adapter->getS3Client()->putObjectAsync([
                    'Bucket' => $this->adapter->getBucket(),
                    'Key' => $this->path(),
                    'Body' => $contents,
                    'ContentType' => $mimeType, // Set the MIME type
                ])->then(
                    function ($data = null) use ($resolve, $length) {
                        $this->deactivate();
                        $resolve($length);
                    },
                    function ($error) use ($reject) {
                        $this->deactivate();
                        $reject($error);
                    }
                );
            } catch (\Throwable $e) {
                $reject($e);
            }
        });
    }

    private static array $mime_types = [
        'aac' => 'audio/aac',
        'abw' => 'application/x-abiword',
        'arc' => 'application/x-freearc',
        'avif' => 'image/avif',
        'avi' => 'video/x-msvideo',
        'azw' => 'application/vnd.amazon.ebook',
        'bin' => 'application/octet-stream',
        'bmp' => 'image/bmp',
        'bz' => 'application/x-bzip',
        'bz2' => 'application/x-bzip2',
        'cda' => 'application/x-cdf',
        'csh' => 'application/x-csh',
        'css' => 'text/css',
        'csv' => 'text/csv',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'eot' => 'application/vnd.ms-fontobject',
        'epub' => 'application/epub+zip',
        'gz' => 'application/gzip',
        'gif' => 'image/gif',
        'htm' => 'text/html',
        'html' => 'text/html',
        'ico' => 'image/vnd.microsoft.icon',
        'ics' => 'text/calendar',
        'jar' => 'application/java-archive',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'jsonld' => 'application/ld+json',
        'mid' => 'audio/midi audio/x-midi',
        'midi' => 'audio/midi audio/x-midi',
        'mjs' => 'text/javascript',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'mpeg' => 'video/mpeg',
        'mpkg' => 'application/vnd.apple.installer+xml',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'oga' => 'audio/ogg',
        'ogv' => 'video/ogg',
        'ogx' => 'application/ogg',
        'opus' => 'audio/opus',
        'otf' => 'font/otf',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
        'php' => 'application/x-httpd-php',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'rar' => 'application/vnd.rar',
        'rtf' => 'application/rtf',
        'sh' => 'application/x-sh',
        'svg' => 'image/svg+xml',
        'swf' => 'application/x-shockwave-flash',
        'tar' => 'application/x-tar',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'ts' => 'video/mp2t',
        'ttf' => 'font/ttf',
        'txt' => 'text/plain',
        'vsd' => 'application/vnd.visio',
        'wav' => 'audio/wav',
        'weba' => 'audio/webm',
        'webm' => 'video/webm',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'xhtml' => 'application/xhtml+xml',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml' => 'text/xml',
        'xul' => 'application/vnd.mozilla.xul+xml',
        'zip' => 'application/zip',
        '3gp' => 'video/3gpp',
        '3g2' => 'video/3gpp2',
        '7z' => 'application/x-7z-compressed'
    ];

}
