<?php

namespace ReactphpX\FilesystemS3;

use Aws\S3\S3Client;
use React\EventLoop\Loop;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Node;
use React\Promise\PromiseInterface;
use React\Filesystem\PollInterface;
use React\Promise\Promise;
use GuzzleHttp\HandlerStack;

final class Adapter implements AdapterInterface
{
    private S3Client $s3;
    private string $bucket;

    private PollInterface $poll;

    public function __construct($s3Options = [], string $bucket)
    {
        $this->s3 = new S3Client(array_merge([
            'http_handler' => HandlerStack::create(new HttpClientAdapter()),
        ],$s3Options));
        $this->bucket = $bucket;
        $this->poll = new Poll(Loop::get());
    }

    public function detect(string $path): PromiseInterface
    {   
        throw new \RuntimeException("Not implemented");
    }

    public function directory(string $path): Node\DirectoryInterface
    {

        return new Directory($this, ltrim(dirname($path), '.') . DIRECTORY_SEPARATOR, basename($path));
    }

    public function file(string $path): Node\FileInterface
    {
        return new File($this, dirname($path) . DIRECTORY_SEPARATOR, basename($path));
    }

    public function getS3Client(): S3Client
    {
        return $this->s3;
    }

    public function getBucket(): string
    {
        return $this->bucket;
    }

    public function getPoll(): PollInterface
    {
        return $this->poll;
    }

    protected function activate(): void
    {
        $this->poll->activate();
    }

    protected function deactivate(): void
    {
        $this->poll->deactivate();
    }
}
