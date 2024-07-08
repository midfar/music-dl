<?php

/** @noinspection PhpMissingParentCallCommonInspection */

declare(strict_types=1);

/**
 * Copyright (c) 2019-2024 guanguans<ityaozm@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/guanguans/music-dl
 */

namespace App\Commands;

use App\Concerns\Hydrator;
use App\Contracts\Music;
use App\Support\Utils;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Laravel\Prompts;
use LaravelZero\Framework\Commands\Command;
use SebastianBergmann\Timer\ResourceUsageFormatter;
use SebastianBergmann\Timer\Timer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

final class MusicCommand extends Command
{
    use Hydrator;

    /** The signature of the command. */
    protected $signature = <<<'SIGNATURE'
        music
        {keyword? : Search keyword for music}
        {--driver=sequence : Specify the search driver(fork、sequence)}
        {--d|dir= : Specify the download directory}
        {--l|lang= : Specify the language}
        {--no-continue : Specify whether to recall the command after the download is complete}
        {--sources=* : Specify the music sources(tencent、netease、kugou)}
        SIGNATURE;

    /** The description of the command. */
    protected $description = 'Search and download music';
    private Music $music;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        collect()
            ->when(!$this->laravel->has('logo'), function (): void {
                Prompts\info(config('app.logo'));
                $this->laravel->instance('logo', config('app.logo'));
            })
            ->when(windows_os(), static fn () => warning(__('windows_hint')))
            ->tap(function () use (&$keyword): void {
                $keyword = str($this->argument('keyword') ?? text(
                    __('keyword_label'),
                    __('keyword_placeholder'),
                    __('keyword_default'),
                    __('keyword_label')
                ))->trim()->toString();
            })
            ->pipe(function () use ($keyword, &$duration): Collection {
                return spin(
                    function () use ($keyword, &$duration): Collection {
                        /** @noinspection PhpVoidFunctionResultUsedInspection */
                        $timer = tap(new Timer)->start();
                        $songs = $this->music->search($keyword, $this->option('sources'));
                        $duration = $timer->stop();

                        return $songs;
                    },
                    __('searching_hint')
                );
            })
            ->whenEmpty(function (): void {
                warning(__('empty_hint'));
                $this->handle();
            })
            ->tap(function (Collection $songs) use ($keyword, $duration): void {
                table(__('table_header'), $this->sanitizes($songs, $keyword));
                Prompts\info((new ResourceUsageFormatter)->resourceUsage($duration));
            })
            ->tap(fn (): bool => confirm(__('confirm_label')) or $this->handle())
            ->tap(function (Collection $songs) use (&$selectedKeys, $keyword): void {
                $selectedKeys = $this
                    ->hydrates($songs, $keyword)
                    ->pipe(static fn (Collection $options): Collection => collect(multiselect(
                        __('select_label'),
                        $options->all(),
                        [$options->first()],
                        20,
                        __('select_label'),
                        hint: __('select_hint'),
                    ))->map(static fn (string $selectedValue) => $options->search($selectedValue)));
            })
            ->pipe(
                static fn (Collection $songs): Collection => \in_array(0, $selectedKeys->all(), true)
                    ? $songs : $songs->only($selectedKeys->all())
            )
            ->each(fn (array $song): mixed => $this->wrappedExceptionHandler(fn () => $this->music->download(
                $song['url'],
                Utils::savePathFor($song, $this->option('dir'))
            )))
            ->tap(fn (): mixed => $this->wrappedExceptionHandler(fn () => $this->notify(
                config('app.name'),
                $this->option('dir'),
                resource_path('notify-icon.png')
            )))
            ->when(!$this->option('no-continue'), fn (): null => $this->handle());
    }

    /**
     * Define the command's schedule.
     *
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->music = \App\Facades\Music::driver($this->option('driver'));
        $this->input->setOption('dir', $this->option('dir') ?: Utils::defaultSaveDir());
        $this->option('lang') and config()->set('app.locale', $this->option('lang'));
        File::ensureDirectoryExists($this->option('dir'));
        $this->input->setOption('sources', array_filter((array) $this->option('sources')) ?: config('app.sources'));
    }

    private function wrappedExceptionHandler(callable $callback, mixed ...$parameters): mixed
    {
        try {
            return $callback(...$parameters);
        } catch (\Throwable $throwable) {
            error($throwable->getMessage());

            return $throwable;
        }
    }
}
