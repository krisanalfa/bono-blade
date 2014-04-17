<?php

/**
 * Bono - PHP5 Web Framework
 *
 * MIT LICENSE
 *
 * Copyright (c) 2013 PT Sagara Xinix Solusitama
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @category  View
 * @package   Bono
 * @author    Krisan Alfa Timur <krisan47@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @license   https://raw.github.com/xinix-technology/bono/master/LICENSE MIT
 * @link      https://github.com/krisanalfa/bonoblade
 */
namespace KrisanAlfa\Blade;

use Bono\App;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Environment;
use Illuminate\View\FileViewFinder;

/**
 * A Blade Template Engine for Bono PHP Framework
 *
 * @category  View
 * @package   Bono
 * @author    Krisan Alfa Timur <krisan47@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @license   https://raw.github.com/xinix-technology/bono/master/LICENSE MIT
 * @link      https://github.com/krisanalfa/bonoblade
 */
class BonoBlade extends \Slim\View
{
    /**
     * Array containg paths where to look for blade files
     *
     * @var array
     */
    protected $viewPaths;

    /**
     * Location where to store cached views
     *
     * @var string
     */
    protected $cachePath;

    /**
     * A slim container for Blade ecosystem
     *
     * @var Illuminate\Container\Container
     */
    protected $container;

    /**
     * Blade View instance
     *
     * @var Illuminate\View\Environment
     */
    protected $instance;

    /**
     * The layout name, usefull for share variable between layout and template
     * @var string
     */
    protected $layoutName;

    /**
     * The main layout
     *
     * @var string
     */
    protected $layout = null;

    /**
    * Initalizer a.k.a the class constructor
    * Leave the third arguments empty if you won't use any layout
    *
    * @param array  $viewPaths  The path where your template resides
    * @param string $cachePath  The path where you want to store the view cache
    * @param string $layoutName The main layout you want to use
    */
    public function __construct($viewPaths = array(), $cachePath = '', $layoutName = null)
    {
        parent::__construct();

        $this->app       = App::getInstance();

        $config          = $this->app->config('bono.blade');

        $this->viewPaths = (array) @$config['templates'] ?: (array) $viewPaths;

        $this->cachePath = @$config['cache'] ?: $cachePath;

        $this->container = new Container;

        $this->registerFilesystem();

        $this->registerEvents();

        $this->registerEngineResolver();

        $this->registerViewFinder();

        $this->instance = $this->registerEnvironment();

        // Set the layout
        $this->setLayout($layoutName);

        return $this;
    }

    /**
    * Register the filesystem for Blade ecosystem
    *
    * @return void
    */
    protected function registerFilesystem()
    {
        $this->container->bindShared(
            'files',
            function () {
                return new Filesystem;
            }
        );
    }

    /**
    * Register the view event
    *
    * @return void
    */
    protected function registerEvents()
    {
        $this->container->bindShared(
            'events',
            function () {
                return new Dispatcher;
            }
        );
    }

    /**
     * Register the engine resolver instance.
     *
     * @return void
     */
    protected function registerEngineResolver()
    {
        $mySelf = $this;

        $this->container->bindShared(
            'view.engine.resolver',
            function ($app) use ($mySelf) {
                $resolver = new EngineResolver;

                // Next we will register the various engines with the resolver so that the
                // environment can resolve the engines it needs for various views based
                // on the extension of view files. We call a method for each engines.
                foreach (array('php', 'blade') as $engine) {
                    $mySelf->{'register' . ucfirst($engine) . 'Engine'}($resolver);
                }

                return $resolver;
            }
        );
    }

    /**
     * Register the PHP engine implementation.
     *
     * @param \Illuminate\View\Engines\EngineResolver $resolver Engine Resolver
     *
     * @return void
     */
    protected function registerPhpEngine($resolver)
    {
        $resolver->register(
            'php',
            function () {
                return new PhpEngine;
            }
        );
    }

    /**
     * Register the Blade engine implementation.
     *
     * @param \Illuminate\View\Engines\EngineResolver $resolver Engine Resolver
     *
     * @return void
     */
    protected function registerBladeEngine($resolver)
    {
        $mySelf    = $this;
        $container = $this->container;

        // The Compiler engine requires an instance of the CompilerInterface, which in
        // this case will be the Blade compiler, so we'll first create the compiler
        // instance to pass into the engine so it can compile the views properly.
        $this->container->bindShared(
            'blade.compiler',
            function ($container) use ($mySelf) {
                $cache = $mySelf->cachePath;

                return new BladeCompiler($container['files'], $cache);
            }
        );

        $resolver->register(
            'blade',
            function () use ($container) {
                return new CompilerEngine($container['blade.compiler'], $container['files']);
            }
        );
    }

    /**
     * Register the view finder implementation.
     *
     * @return void
     */
    protected function registerViewFinder()
    {
        $mySelf = $this;

        $this->container->bindShared(
            'view.finder',
            function ($app) use ($mySelf) {
                $paths = array_merge_recursive(App::getInstance()->theme->getBaseDirectory(), $mySelf->viewPaths);
                $paths = $mySelf->arrayFlatten($paths);

                foreach ($paths as $key => $path) {
                    if (count(explode('templates', $path)) > 1) {
                        continue;
                    }
                    $paths[$key] = $path . DIRECTORY_SEPARATOR . 'templates';
                }

                $mySelf->viewPaths = $paths;

                return new FileViewFinder($app['files'], $paths);
            }
        );
    }

    protected function arrayFlatten($array)
    {
        $return = array();

        array_walk_recursive($array, function ($x) use (&$return) {
            $return[] = $x;
        });

        return $return;
    }

    /**
     * Register the view environment.
     *
     * @return Illuminate\View\Environment
     */
    protected function registerEnvironment()
    {
        // Next we need to grab the engine resolver instance that will be used by the
        // environment. The resolver will be used by an environment to get each of
        // the various engine implementations such as plain PHP or Blade engine.
        $resolver   = $this->container['view.engine.resolver'];
        $finder     = $this->container['view.finder'];
        $events     = $this->container['events'];
        $env        = new Environment($resolver, $finder, $events);

        // We will also set the container instance on this view environment since the
        // view composers may be classes registered in the container, which allows
        // for great testable, flexible composers for the application developer.
        $env->setContainer($this->container);

        return $env;
    }

    /**
     * Manually set the layout
     *
     * This method will output the rendered template content, so you can set the layout on the air
     *
     * @param string $layout name
     *
     * @return void
     */
    public function setLayout($layout)
    {
        $app              = App::getInstance();
        $layout           = $app->theme->resolve($layout);
        $this->layout     = $this->make($layout, $this->all());
        $this->layoutName = $layout;
    }

    /**
     * Render Blade Template
     *
     * This method will output the rendered template content
     *
     * @param string $template The path to the Blade template, relative to the Blade templates directory.
     * @param array  $data     The data that will be passed to the view
     *
     * @return void
     */
    public function render($template, $data = array())
    {
        $data     = array_merge_recursive($this->all(), $data);
        $app      = App::getInstance();
        $template = $this->resolve($template);

        $app->response->template($template);

        $compiled = $this->make($this->layoutName, $data)->nest('content', $template, $data);

        try {
            $compiled->__toString();
        } catch (\RuntimeException $e) {
            $app->error($e);
        }

        return (string) $compiled;
    }

    /**
    * Parsing the path of file
    *
    * This method will try to find the existing of template file
    *
    * @param string $path The relative template path
    *
    * @return string
    */
    protected function resolve($path)
    {
        $explodedPath = explode(DIRECTORY_SEPARATOR, $path);

        // If it's like /resource/:id/:method
        if (count($explodedPath) > 1) {
            // Template priority:
            // 1) /[templatedir]/[:resource]/[:method]
            // 2) /[templatedir]/shared/[:method]
            foreach ($this->viewPaths as $viewPath) {

                // Get the specific resource template
                $template = $this->resourceTemplateIsExist($viewPath, $explodedPath);
                if (! is_null($template)) {
                    return $template;
                }

                // Get shared template
                $template = $this->sharedTemplateIsExist($viewPath, $explodedPath);
                if (! is_null($template)) {
                    return $template;
                }
            }
        }

        return $path;
    }

    /**
    * The resource template is exist
    *
    * This method will find out if the resource template is exist
    * For example the URL /resource/:id/:method
    * This method will try to resolve for /templatedir/:resource/:method
    *
    * @param string $viewPath     The view / template path we want to check
    * @param array  $explodedPath The exploded template path
    *
    * @return mixed If the resource template is exist, this method will return the template path,
    *               but if not, this method will return null
    */
    protected function resourceTemplateIsExist($viewPath, $explodedPath)
    {
        $path  = null;
        $glued = implode(DIRECTORY_SEPARATOR, $explodedPath);
        $file  = realpath($viewPath . DIRECTORY_SEPARATOR . $glued . '.blade.php');

        if (is_readable($file)) {
            $path = implode('.', $explodedPath);
        }

        return $path;
    }

    /**
    * The shared template is exist
    *
    * This method will find out if the shared template is exist
    * For example: The URL /resource/:id/:action
    * This method will try to resolve for /templatedir/shared/:action
    *
    * @param string $viewPath     The view / template path
    * @param array  $explodedPath The exploded template path
    *
    * @return mixed If the shared template is exist, this method will return the template path,
    *               but if not, this method will return null
    */
    protected function sharedTemplateIsExist($viewPath, $explodedPath)
    {
        $path = null;
        $tail = end($explodedPath);
        $file = realpath($viewPath . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . $tail . '.blade.php');

        if (is_readable($file)) {
            $path = 'shared.' . $tail;
        }

        return $path;
    }

    /**
     * Magic function to call Illuminate\View methods
     *
     * @param function  $method The static View method
     * @param arguments $args   The arguments
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        $view = $this->instance;

        return call_user_func_array(array($view, $method), $args);
    }
}
