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

    public function putContents(string $contents, int $flags = 0): PromiseInterface
    {
        $this->activate();
        return new Promise(function ($resolve, $reject) use ($contents) {
            try {
                $length = strlen($contents);
                $this->adapter->getS3Client()->putObjectAsync([
                    'Bucket' => $this->adapter->getBucket(),
                    'Key' => $this->path(),
                    'Body' => $contents,
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
}
