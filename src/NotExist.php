<?php

namespace ReactphpX\FilesystemS3;

use React\Filesystem\Node\NotExistInterface;
use React\Promise\PromiseInterface;
use React\Promise\Promise;

final class NotExist implements NotExistInterface
{
    private Adapter $adapter;
    private string $directory;
    private string $name;

    public function __construct(Adapter $adapter, string $directory, string $name)
    {
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
        return new Promise(function ($resolve) {
            $resolve(null);
        });
    }

    public function unlink(): PromiseInterface
    {
        return new Promise(function ($resolve) {
            $resolve(true);
        });
    }

    public function createDirectory(): PromiseInterface
    {
        return new Promise(function ($resolve) {
            $resolve(true);
        });
    }

    public function createFile(): PromiseInterface
    {
        return new Promise(function ($resolve) {
            $resolve(true);
        });
    }
}