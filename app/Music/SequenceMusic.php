<?php

declare(strict_types=1);

/**
 * This file is part of the guanguans/music-dl.
 *
 * (c) guanguans <ityaozm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace App\Music;

use App\Concerns\HttpClientFactory;
use App\Contracts\Music;
use App\Support\Meting;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use Rahul900Day\LaravelConsoleSpinner\Spinner;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * @method \Rahul900Day\LaravelConsoleSpinner\Spinner spinner(int $max = 0);
 * @method null|\Iterator|void withSpinner($totalSteps, \Closure $callback, string $message = '', array $options = []);
 *
 * @see \Rahul900Day\LaravelConsoleSpinner\SpinnerMixin
 */
class SequenceMusic implements \App\Contracts\HttpClientFactory, Music
{
    use HttpClientFactory;
    use Macroable;

    public function __construct(
        protected Meting $meting,
        protected OutputStyle $output
    ) {
        $this->meting = $meting->format();
    }

    /**
     * @psalm-suppress NamedArgumentNotAllowed
     *
     * @throws \JsonException
     */
    public function search(string $keyword, array $sources = []): array
    {
        $withoutUrlSongs = collect($sources)
            ->map(fn (string $source): array => json_decode(
                $this->meting->site($source)->search($keyword),
                true,
                512,
                JSON_THROW_ON_ERROR
            ))
            ->collapse()
            ->all();

        return collect($this->ensureWithUrl($withoutUrlSongs))
            ->filter(static fn (array $song): bool => ! empty($song['url']))
            ->values()
            ->all();
    }

    /**
     * @psalm-suppress UnusedVariable
     *
     * @throws GuzzleException
     */
    public function download(string $url, string $savePath): void
    {
        $this->createHttpClient()->get($url, [
            'sink' => $savePath,
            'progress' => function (int $totalDownload, int $downloaded) use (&$progressBar, &$isDownloaded): void {
                if ($totalDownload > 0 && $downloaded > 0 && ! $progressBar instanceof ProgressBar) {
                    $progressBar = new ProgressBar($this->output, $totalDownload);
                    $progressBar->start();
                }

                if (! $isDownloaded && $progressBar instanceof ProgressBar && $totalDownload === $downloaded) {
                    $progressBar->finish();
                    $isDownloaded = true;
                }

                $progressBar and $progressBar->setProgress($downloaded);
            },
        ]);
    }

    /**
     * @throws \JsonException
     */
    protected function ensureWithUrl(array $withoutUrlSongs): array
    {
        $songs = tap(collect(), function (Collection $songs) use ($withoutUrlSongs): void {
            $this->withSpinner(
                $withoutUrlSongs,
                fn (array $withoutUrlSong): Collection => $songs->add($withoutUrlSong + $this->requestUrl($withoutUrlSong)),
                config('console-spinner.message'),
                ['bar_character' => config('console-spinner.bar_character')]
            );
        });

        return $songs->all();
    }

    protected function createSpinner(array $withoutUrlSongs): Spinner
    {
        $spinner = $this->spinner(\count($withoutUrlSongs));
        $spinner->setBarCharacter(config('console-spinner.bar_character'));
        $spinner->setMessage(config('console-spinner.message'));
        $spinner->start();

        return $spinner;
    }

    protected function toConcurrency(array $withoutUrlSongs): int
    {
        return min(\count($withoutUrlSongs), 128);
    }

    /**
     * @throws \JsonException
     */
    protected function requestUrl(array $song): array
    {
        return (array) json_decode(
            $this->meting->site($song['source'])->url($song['url_id']),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
