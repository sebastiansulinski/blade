<?php

namespace SSD\Blade;

use Illuminate\View\View;
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
     * Paths to the directories containing view files.
     *
     * @var array
     */
    private $viewPaths = [];

    /**
     * Path to the view cache directory.
     *
     * @var string
     */
    private $cachePath;

    /**
     * Container instance.
     *
     * @var Container
     */
    public $app;

    /**
     * Blade constructor.
     *
     * @param string|array $viewPaths
     * @param string $cachePath
     * @param Container $app
     * @param Dispatcher $events
     */
    public function __construct(
        $viewPaths,
        $cachePath,
        Container $app = null,
        Dispatcher $events = null
    )
    {
        $this->viewPaths = (array) $viewPaths;
        $this->cachePath = $cachePath;
        $this->app = $app ?: new Container;

        $this->registerFileSystem(new Filesystem);
        $this->registerEvents($events ?: new Dispatcher);
        $this->registerEngineResolver(new EngineResolver);
        $this->registerBladeCompiler();
        $this->registerViewFinder();
        $this->registerFactory();
    }

    /**
     * Bind file system.
     *
     * @param Filesystem $filesystem
     * @return void
     */
    private function registerFileSystem(Filesystem $filesystem)
    {
        $this->app->singleton('files', function() use ($filesystem) {
            return $filesystem;
        });
    }

    /**
     * Bind event dispatcher.
     *
     * @param Dispatcher $events
     * @return void
     */
    private function registerEvents(Dispatcher $events)
    {
        $this->app->singleton('events', function() use ($events) {
            return $events;
        });
    }

    /**
     * Bind blade compiler.
     *
     * @return void
     */
    private function registerBladeCompiler()
    {
        $this->app->singleton('blade.compiler', function($app) {

            return new BladeCompiler(
                $app['files'],
                $this->cachePath
            );

        });
    }

    /**
     * Bind engine resolver.
     *
     * @param EngineResolver $resolver
     * @return void
     */
    private function registerEngineResolver(EngineResolver $resolver)
    {
        $this->app->singleton('view.engine.resolver', function($app) use ($resolver) {

            $this->registerPhpEngine($resolver);
            $this->registerBladeEngine($resolver, $app);

            return $resolver;

        });
    }

    /**
     * Bind php engine.
     *
     * @param EngineResolver $resolver
     * @return void
     */
    private function registerPhpEngine(EngineResolver $resolver)
    {
        $resolver->register('php', function() {
            return new PhpEngine;
        });
    }

    /**
     * Bind blade engine.
     *
     * @param EngineResolver $resolver
     * @param Container $app
     */
    private function registerBladeEngine(EngineResolver $resolver, Container $app)
    {
        $resolver->register('blade', function() use($app) {
            return new CompilerEngine($app['blade.compiler']);
        });
    }

    /**
     * Bind view finder.
     *
     * @return void
     */
    private function registerViewFinder()
    {
        $this->app->singleton('view.finder', function($app) {

            return new FileViewFinder(
                $app['files'],
                $this->viewPaths
            );

        });
    }

    /**
     * Get view factory instance.
     *
     * @return Factory
     */
    private function registerFactory()
    {
        $this->app->singleton('view', function($app) {

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
     * @param null $view
     * @param array $data
     * @param array $mergeData
     * @return Factory|View
     */
    public function view($view = null, $data = [], $mergeData = [])
    {
        if (func_num_args() === 0) {
            return $this->app['view'];
        }

        return $this->app['view']->make($view, $data, $mergeData);
    }

}