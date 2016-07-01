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
     * IOC Container.
     *
     * @var Container
     */
    public $container;

    /**
     * View factory
     *
     * @var Factory
     */
    private $factory;

    /**
     * Blade constructor.
     *
     * @param string|array $viewPaths
     * @param string $cachePath
     * @param Container $container
     * @param Dispatcher $events
     */
    public function __construct(
        $viewPaths,
        $cachePath,
        Container $container = null,
        Dispatcher $events = null
    )
    {
        $this->viewPaths = (array) $viewPaths;
        $this->cachePath = $cachePath;
        $this->container = $container ?: new Container;

        $this->bindFileSystem();
        $this->bindEvents($events ?: new Dispatcher);
        $this->bindEngineResolver(new EngineResolver);
        $this->bindBladeCompiler();
        $this->bindViewFinder();

        $this->factory = $this->factory();
    }

    /**
     * Bind file system.
     *
     * @return void
     */
    private function bindFileSystem()
    {
        $this->container->singleton('files', function() {
            return new Filesystem;
        });
    }

    /**
     * Bind event dispatcher.
     *
     * @param Dispatcher $events
     * @return void
     */
    private function bindEvents(Dispatcher $events)
    {
        $this->container->singleton('events', function() use ($events) {
            return $events;
        });
    }

    /**
     * Bind blade compiler.
     *
     * @return void
     */
    private function bindBladeCompiler()
    {
        $this->container->singleton('blade.compiler', function() {

            return new BladeCompiler(
                $this->container['files'],
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
    private function bindEngineResolver(EngineResolver $resolver)
    {
        $this->container->singleton('view.engine.resolver', function() use ($resolver) {

            $this->bindPhpEngine($resolver);
            $this->bindBladeEngine($resolver);

            return $resolver;

        });
    }

    /**
     * Bind php engine.
     *
     * @param EngineResolver $resolver
     * @return void
     */
    private function bindPhpEngine(EngineResolver $resolver)
    {
        $resolver->register('php', function() {
            return new PhpEngine;
        });
    }

    /**
     * Bind blade engine.
     *
     * @param EngineResolver $resolver
     * @return void
     */
    private function bindBladeEngine(EngineResolver $resolver)
    {
        $resolver->register('blade', function() {

            return new CompilerEngine(
                $this->container['blade.compiler'],
                $this->container['files']
            );

        });
    }

    /**
     * Bind view finder.
     *
     * @return void
     */
    private function bindViewFinder()
    {
        $this->container->singleton('view.finder', function() {

            return new FileViewFinder(
                $this->container['files'],
                $this->viewPaths
            );

        });
    }

    /**
     * Get view factory instance.
     *
     * @return Factory
     */
    private function factory()
    {
        $factory = new Factory(
            $this->container['view.engine.resolver'],
            $this->container['view.finder'],
            $this->container['events']
        );

        $factory->setContainer($this->container);

        return $factory;
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
            return $this->factory;
        }

        return $this->factory->make($view, $data, $mergeData);
    }

}