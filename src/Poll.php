<?php

namespace ReactphpX\FilesystemS3;

use React\EventLoop\TimerInterface;
use React\Filesystem\PollInterface;
use React\EventLoop\LoopInterface;

final class Poll implements PollInterface
{
    private LoopInterface $loop;
    private int $workInProgress = 0;
    private ?TimerInterface $workInProgressTimer = null;
    private float $workInterval = 0.01;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function activate(): void
    {
        if ($this->workInProgress++ === 0) {
            $this->workInProgressTimer = $this->loop->addPeriodicTimer($this->workInterval, static function () {
                if (!\GuzzleHttp\Promise\Utils::queue()->isEmpty()){
                    \GuzzleHttp\Promise\Utils::queue()->run();
                }
            });
        }
    }

    public function deactivate(): void
    {
        if (--$this->workInProgress <= 0) {
            $this->loop->cancelTimer($this->workInProgressTimer);
        }
    }
}
