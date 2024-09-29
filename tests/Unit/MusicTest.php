<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection AnonymousFunctionStaticInspection */
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

/**
 * Copyright (c) 2019-2024 guanguans<ityaozm@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/guanguans/music-dl
 */

namespace Tests\Unit;

use App\Music;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Concurrency;

it('can search songs', function (): void {
    expect(app(Music::class)->setDriver(Concurrency::driver(config('concurrency.default'))))
        ->search('腰乐队', config('app.sources'))->toBeInstanceOf(Collection::class);
})->group(__DIR__, __FILE__);

it('will throw ConnectException when download failed', function (): void {
    Music::setHttpClient(null);
    app(Music::class)->download('foo.mp3', downloads_path('foo.mp3'));
})->group(__DIR__, __FILE__)->throws(ConnectException::class);

it('will download song', function (): void {
    Music::setHttpClient(new Client([
        'handler' => HandlerStack::create(new MockHandler([
            new Response(body: 'foo'),
        ])),
    ]));
    expect(app(Music::class))
        ->download('foo.mp3', $path = downloads_path('foo.mp3'))->toBeNull()
        ->and($path)->toBeFile();
})->group(__DIR__, __FILE__)->depends('it will throw ConnectException when download failed');
