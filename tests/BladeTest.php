<?php

namespace SSDTest;

use SSD\Blade\Blade;

use stdClass;
use Illuminate\View\View;
use Illuminate\View\Factory;
use PHPUnit\Framework\TestCase;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\View\FileViewFinder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Compilers\BladeCompiler;

class BladeTest extends TestCase
{
    /**
     * @var Blade
     */
    private $blade;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->blade = new Blade(
            realpath(__DIR__ . '/views'),
            realpath(__DIR__ . '/cache')
        );
    }

    /**
     * @test
     */
    public function returns_instance_of_container()
    {
        $this->assertInstanceOf(
            Container::class,
            $this->blade->view()->getContainer(),
            'Blade::view()::getContainer() did not return instance of Container'
        );

        $this->assertInstanceOf(
            Container::class,
            $this->blade->app,
            'Blade::$container did not return instance of Container'
        );
    }

    /**
     * @test
     */
    public function returns_instance_of_filesystem()
    {
        $this->assertInstanceOf(
            Filesystem::class,
            $this->blade->app['files'],
            'Blade::$container[files] did not return instance of Filesystem'
        );
    }

    /**
     * @test
     */
    public function returns_instance_of_event_dispatcher()
    {
        $this->assertInstanceOf(
            Dispatcher::class,
            $this->blade->app['events'],
            'Blade::$container[events] did not return instance of event Dispatcher'
        );
    }

    /**
     * @test
     */
    public function returns_instance_of_blade_compiler()
    {
        $this->assertInstanceOf(
            BladeCompiler::class,
            $this->blade->app['blade.compiler'],
            'Blade::$container[blade.compiler] did not return instance of BladeCompiler'
        );
    }

    /**
     * @test
     */
    public function returns_instance_of_resolvers()
    {
        $this->assertInstanceOf(
            EngineResolver::class,
            $this->blade->app['view.engine.resolver'],
            'Blade::$container[view.engine.resolver] did not return instance of EngineResolver'
        );

        $this->assertInstanceOf(
            PhpEngine::class,
            $this->blade->app['view.engine.resolver']->resolve('php'),
            'Blade::$container[view.engine.resolver]->resolve(php) did not return instance of PhpEngine'
        );

        $this->assertInstanceOf(
            CompilerEngine::class,
            $this->blade->app['view.engine.resolver']->resolve('blade'),
            'Blade::$container[view.engine.resolver]->resolve(blade) did not return instance of CompilerEngine'
        );
    }

    /**
     * @test
     */
    public function returns_instance_of_view_finder()
    {
        $this->assertInstanceOf(
            FileViewFinder::class,
            $this->blade->app['view.finder'],
            'Blade::$container[view.finder] did not return instance of FileViewFinder'
        );
    }

    /**
     * @test
     */
    public function returns_instance_of_the_view_factory()
    {
        $this->assertInstanceOf(
            Factory::class,
            $this->blade->view(),
            'Blade::view() did not return instance of view Factory'
        );
    }

    /**
     * @test
     */
    public function returns_instance_of_the_view()
    {
        $this->assertInstanceOf(
            View::class,
            $this->blade->view('index'),
            'Blade::view(index) did not return instance of view View'
        );
    }

    /**
     * @test
     */
    public function returns_rendered_view()
    {
        $user = new stdClass;
        $user->name = 'Sebastian';

        $this->assertEquals(
            '<p>Hallo Sebastian</p>',
            $this->blade->view('index', compact('user')),
            'Blade::view(index, compact(user)) did not return correct string'
        );

        $this->assertEquals(
            '<p>Hallo Sebastian</p>',
            $this->blade->view('index', ['user' => $user]),
            'Blade::view(index, [user => $user]) did not return correct string'
        );

        $this->assertEquals(
            '<p>Hallo Sebastian</p>',
            $this->blade->view('index')->with('user', $user),
            'Blade::view(index)->with(user, $user) did not return correct string'
        );
    }

    /**
     * @test
     */
    public function determines_if_the_view_exists()
    {
        $this->assertFalse(
            $this->blade->view()->exists('test'),
            'Blade::view()->exists() found test view'
        );
    }

    /**
     * @test
     */
    public function sharing_variables_with_all_views()
    {
        $user = new stdClass;
        $user->name = 'Martin';

        $this->blade->view()->share('user', $user);

        $this->assertEquals(
            '<p>Hallo Martin</p>',
            $this->blade->view('index'),
            'Blade::view()->share() failed with index'
        );

        $this->assertEquals(
            '<p>Hallo Martin</p>',
            $this->blade->view('composer'),
            'Blade::view()->share() failed with composer'
        );
    }

    /**
     * @test
     */
    public function works_with_composer()
    {
        $this->blade->view()->composer('composer', function(View $view) {

            $user = new stdClass;
            $user->name = 'Martin';

            $view->with('user', $user);

        });

        $this->assertEquals(
            '<p>Hallo Martin</p>',
            $this->blade->view('composer'),
            'Blade::view()->composer() failed'
        );
    }
}