<?php

namespace ReactphpX\FilesystemS3;

use React\Filesystem\Node\DirectoryInterface;
use React\Filesystem\Node\NodeInterface;
use React\Filesystem\Stat;
use React\Promise\PromiseInterface;
use React\Promise\Promise;
use React\Filesystem\PollInterface;

final class Directory implements DirectoryInterface
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
            $this->adapter->getS3Client()->deleteObjectsAsync([
                'Bucket' => $this->adapter->getBucket(),
                'Delete' => [
                    'Objects' => [
                        [
                            'Key' => $this->path(),
                        ],
                    ],
                ],
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

    public function ls(): PromiseInterface
    {
        $this->activate();
        return new Promise(function ($resolve, $reject) {
            $prefix = rtrim($this->path(), '/') . '/';
            $this->adapter->getS3Client()->listObjectsV2Async([
                'Bucket' => $this->adapter->getBucket(),
                'Prefix' => $prefix,
                'Delimiter' => '/',
            ])->then(
                function ($result) use ($resolve, $prefix) {
                    $nodes = [];
                    // Handle files
                    if (isset($result['Contents'])) {
                        foreach ($result['Contents'] as $object) {
                            if ($object['Key'] === $prefix) {
                                continue; // Skip the directory itself
                            }
                            $nodes[] = $this->adapter->file($object['Key']);
                        }
                    }

                    // Handle subdirectories
                    if (isset($result['CommonPrefixes'])) {
                        foreach ($result['CommonPrefixes'] as $prefix) {
                            $nodes[] = $this->adapter->directory(rtrim($prefix['Prefix'], '/'));
                        }
                    }

                    $this->deactivate();
                    $resolve($nodes);
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

}