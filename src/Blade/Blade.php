<?php

namespace SSD\Blade;

use Illuminate\View\Factory;
use Illuminate\Events\Dispatcher;
use Illuminate\View\FileViewFinder;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Compilers\BladeCompiler;

class Blade
{
    /**
     * Path(s) to the directories containing view files.
     *
     * @var array
     */
    private $viewPaths = [];

    /**
     * Path to the cache directory.
     *
     * @var string
     */
    private $cachePath;

    /**
     * Container instance.
     *
     * @var \Illuminate\Container\Container
     */
    public $app;

    /**
     * Blade constructor.
     *
     * @param  string|array $viewPaths
     * @param  string $cachePath
     * @param  Container|null $app
     * @param  Dispatcher|null $events
     * @param  \Illuminate\Filesystem\Filesystem|null $filesystem
     */
    public function __construct(
        $viewPaths,
        $cachePath,
        Container $app = null,
        Dispatcher $events = null,
        Filesystem $filesystem = null
    ) {
        $this->viewPaths = (array)$viewPaths;
        $this->cachePath = $cachePath;
        $this->app = $app ?: new Container;

        $this->registerFileSystem($filesystem ?: new Filesystem);
        $this->registerEvents($events ?: new Dispatcher);
        $this->registerEngineResolver(new EngineResolver);
        $this->registerBladeCompiler();
        $this->registerViewFinder();
        $this->registerFactory();
    }

    /**
     * Register file system.
     *
     * @param  \Illuminate\Filesystem\Filesystem $filesystem
     * @return void
     */
    private function registerFileSystem(Filesystem $filesystem): void
    {
        $this->app->singletonIf('files', function () use ($filesystem) {
            return $filesystem;
        });
    }

    /**
     * Register event dispatcher.
     *
     * @param  \Illuminate\Events\Dispatcher $events
     * @return void
     */
    private function registerEvents(Dispatcher $events): void
    {
        $this->app->singletonIf('events', function () use ($events) {
            return $events;
        });
    }

    /**
     * Register blade compiler.
     *
     * @return void
     */
    private function registerBladeCompiler(): void
    {
        $this->app->singleton('blade.compiler', function ($app) {

            return new BladeCompiler(
                $app['files'],
                $this->cachePath
            );

        });
    }

    /**
     * Register view engine resolver.
     *
     * @param  \Illuminate\View\Engines\EngineResolver $resolver
     * @return void
     */
    private function registerEngineResolver(EngineResolver $resolver): void
    {
        $this->app->singleton('view.engine.resolver', function ($app) use ($resolver) {

            $this->registerPhpEngine($resolver);
            $this->registerBladeEngine($resolver, $app);

            return $resolver;

        });
    }

    /**
     * Register php engine.
     *
     * @param  \Illuminate\View\Engines\EngineResolver $resolver
     * @return void
     */
    private function registerPhpEngine(EngineResolver $resolver): void
    {
        $resolver->register('php', function () {
            return new PhpEngine;
        });
    }

    /**
     * Register blade engine.
     *
     * @param  \Illuminate\View\Engines\EngineResolver $resolver
     * @param  \Illuminate\Container\Container $app
     * @return void
     */
    private function registerBladeEngine(EngineResolver $resolver, Container $app): void
    {
        $resolver->register('blade', function () use ($app) {
            return new CompilerEngine($app['blade.compiler']);
        });
    }

    /**
     * Register view finder.
     *
     * @return void
     */
    private function registerViewFinder(): void
    {
        $this->app->singleton('view.finder', function ($app) {

            return new FileViewFinder(
                $app['files'],
                $this->viewPaths
            );

        });
    }

    /**
     * Register view factory.
     *
     * @return void
     */
    private function registerFactory(): void
    {
        $this->app->singleton('view', function ($app) {

            $factory = new Factory(
                $app['view.engine.resolver'],
                $app['view.finder'],
                $app['events']
            );

            $factory->setContainer($app);
            $factory->share('app', $app);

            return $factory;

        });
    }

    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  null $view
     * @param  array $data
     * @param  array $mergeData
     * @return \Illuminate\View\Factory|\Illuminate\View\View
     */
    public function view($view = null, $data = [], $mergeData = [])
    {
        if (func_num_args() === 0) {
            return $this->app['view'];
        }

        return $this->app['view']->make($view, $data, $mergeData);
    }

}
