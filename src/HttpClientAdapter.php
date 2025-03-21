<?php

namespace ReactphpX\FilesystemS3;

use Psr\Http\Message\RequestInterface;
use React\Http\Browser;
use GuzzleHttp\Promise\Promise;
use React\Http\Message\ResponseException;

class HttpClientAdapter
{

    public function __invoke(RequestInterface $request, array $options)
    {

        $promise = new Promise();

        $browser = new Browser();
        $browser->request($request->getMethod(), $request->getUri(), $request->getHeaders(), $request->getBody())
            ->then(function ($response) use ($promise) {
                $promise->resolve($response);
            }, function ($error) use ($promise) {
                $promise->reject($error);
            });

        return $promise;
    }
}
