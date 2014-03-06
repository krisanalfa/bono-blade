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
 * @author      Krisan Alfa Timur <krisan47@gmail.com>
 * @copyright   2013 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/bono
 * @license     https://raw.github.com/xinix-technology/bono/master/LICENSE
 * @package     Bono
 *
 */

namespace KrisanAlfa\Blade;

use Bono\App;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Environment;
use Illuminate\View\FileViewFinder;
use Norm\Model;

class BonoBlade extends \Slim\View {

    /**
     * Array containg paths where to look for blade files
     * @var array
     */
    public $viewPaths;

    /**
     * Location where to store cached views
     * @var string
     */
    public $cachePath;

    /**
     * @var Illuminate\Container\Container
     */
    protected $container;

    /**
     * @var Illuminate\View\Environment
     */
    protected $instance;

    /**
     * The main layout
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
    function __construct($viewPaths, $cachePath, $layoutName = null) {
        parent::__construct();

        $this->viewPaths = (array) $viewPaths;

        $this->cachePath = $cachePath;

        $this->container = new Container;

        $this->registerFilesystem();

        $this->registerEvents();

        $this->registerEngineResolver();

        $this->registerViewFinder();

        $this->instance = $this->registerEnvironment();

        // Set the layout
        if (! is_null($layoutName))
        {
            $this->setLayout($layoutName);
        }

        return $this;
    }

    /**
    * Get the view instance
    * @return void
    */
    public function view()
    {
        return $this->instance;
    }

    /**
    * Register the filesystem for Blade ecosystem
    * @return void
    */
    public function registerFilesystem()
    {
        $this->container->bindShared('files', function()
        {
            return new Filesystem;
        });
    }

    /**
    * Register the view event
    * @return void
    */
    public function registerEvents()
    {
        $this->container->bindShared('events', function()
        {
            return new Dispatcher;
        });
    }

    /**
     * Register the engine resolver instance.
     *
     * @return void
     */
    public function registerEngineResolver()
    {
        $me = $this;

        $this->container->bindShared('view.engine.resolver', function($app) use ($me)
        {
            $resolver = new EngineResolver;

            // Next we will register the various engines with the resolver so that the
            // environment can resolve the engines it needs for various views based
            // on the extension of view files. We call a method for each engines.
            foreach (array('php', 'blade') as $engine)
            {
                $me->{'register'.ucfirst($engine).'Engine'}($resolver);
            }

            return $resolver;
        });
    }

    /**
     * Register the PHP engine implementation.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerPhpEngine($resolver)
    {
        $resolver->register('php', function()
        {
            return new PhpEngine;
        });
    }

    /**
     * Register the Blade engine implementation.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerBladeEngine($resolver)
    {
        $me = $this;
        $app = $this->container;

        // The Compiler engine requires an instance of the CompilerInterface, which in
        // this case will be the Blade compiler, so we'll first create the compiler
        // instance to pass into the engine so it can compile the views properly.
        $this->container->bindShared('blade.compiler', function($app) use ($me)
        {
            $cache = $me->cachePath;

            return new BladeCompiler($app['files'], $cache);
        });

        $resolver->register('blade', function() use ($app)
        {
            return new CompilerEngine($app['blade.compiler'], $app['files']);
        });
    }

    /**
     * Register the view finder implementation.
     *
     * @return void
     */
    public function registerViewFinder()
    {
        $me = $this;
        $this->container->bindShared('view.finder', function($app) use ($me)
        {
            $paths = $me->viewPaths;

            return new FileViewFinder($app['files'], $paths);
        });
    }

    /**
     * Register the view environment.
     *
     * @return Object
     */
    public function registerEnvironment()
    {
        // Next we need to grab the engine resolver instance that will be used by the
        // environment. The resolver will be used by an environment to get each of
        // the various engine implementations such as plain PHP or Blade engine.
        $resolver = $this->container['view.engine.resolver'];

        $finder = $this->container['view.finder'];

        $env = new Environment($resolver, $finder, $this->container['events']);

        // We will also set the container instance on this view environment since the
        // view composers may be classes registered in the container, which allows
        // for great testable, flexible composers for the application developer.
        $env->setContainer($this->container);

        return $env;
    }

    /**
     * Manually set the layout
     *
     * This method will output the rendered template content
     *
     * @param   string $layout name
     * @return  Object
     */
    public function setLayout($layout) {
        $this->layout = $this->view()->make($layout);
        return $this;
    }

    /**
     * Render Blade Template
     *
     * This method will output the rendered template content
     *
     * @param   string  $template  The path to the Blade template, relative to the Blade templates directory.
     * @param   array   $data
     * @param   boolean $useLayout Shall we use the layout?
     * @return  void
     */
    public function render($template, $data = array(), $useLayout = true)
    {
        $template = $this->parsePath($template);

        $data = array_merge_recursive($this->all(), $data);

        if (! is_null($this->layout) and $useLayout) {
            $this->layout->content = $this->view()->make($template, $data);

            echo $this->layout;
            exit();
        }

        echo $this->view()->make($template, $data);
        exit();
    }

    /**
    * Parsing the path of file
    *
    * This method will try to find the existing of template file
    *
    * @param  string  The relative template path
    * @return string
    */
    protected function parsePath($path) {
        $explodedPath = explode('/', $path);

        // If it's like /resource/:function
        if (count($explodedPath) > 1)
        {
            // Template priority:
            // 1) /[templatedir]/[:resource]/[:method]
            // 2) /[templatedir]/shared/[:method]
            // 3) /[templatedir]/[mirrorpath]
            foreach ($this->viewPaths as $viewPath)
            {

                $template = $this->resourceTemplateIsExist($viewPath, $explodedPath);

                if (! is_null($template))
                {
                    $path = $template;
                    break;
                }

                $template = $this->sharedTemplateIsExist($viewPath, $explodedPath);

                if (! is_null($template))
                {
                    $path = $template;
                    break;
                }

                $template = $this->mirrorTemplateIsExist();

                if(! is_null($template))
                {
                    $path = $template;
                    break;
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
    * @param  string  The view / template path
    * @param  array   The exploded template path
    * @return mixed   If the resource template is exist, this method will return the template path,
    *                 but if not, this method will return null
    */
    protected function resourceTemplateIsExist($viewPath, $explodedPath) {
        $path = null;

        // Extension Priority
        // 1) *.blade.php
        // 2) *.php
        // 3) [noExtension]
        $files = array(
            $viewPath . '/' . implode('/', $explodedPath) . '.blade.php',
            $viewPath . '/' . implode('/', $explodedPath) . '.php',
            $viewPath . '/' . implode('/', $explodedPath)
        );

        foreach ($files as $file) {
            if (file_exists($file))
            {
                $path = implode('.', $explodedPath);
                break;
            }
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
    * @param  string  The view / template path
    * @param  array   The exploded template path
    * @return mixed   If the shared template is exist, this method will return the template path,
    *                 but if not, this method will return null
    */
    protected function sharedTemplateIsExist($viewPath, $explodedPath) {
        $path = null;

        // Extension Priority
        // 1) *.blade.php
        // 2) *.php
        // 3) [noExtension]
        $files = array(
            $viewPath . '/shared/' . end($explodedPath) . '.blade.php',
            $viewPath . '/shared/' . end($explodedPath) . '.php',
            $viewPath . '/shared/' . end($explodedPath)
        );

        foreach ($files as $file) {
            if (file_exists($file)) {
                $path = 'shared.' . end($explodedPath);
                break;
            }
        }

        return $path;
    }

    /**
    * Path spliting method based on URL
    *
    * This method will try to find the file based on $_SERVER['PATH_INFO']
    * For example: /:resource/:id/foo/bar/baz will try to resolve /templatedir/:resource/foo/bar/baz
    *
    * @return  mixed  If the file is exsist based on $_SERVER['PATH_INFO'] in templateDirs,
    *                 the return is string, unless it will return null
    */
    protected function mirrorTemplateIsExist() {
        $retVal = null;
        $all = $this->all();
        $entry = isset($all['entry']) ? $all['entry'] : new \StdClass();
        $request = App::getInstance()->request;

        // Is the id of resource return the instance of Norm\Model?
        // If yes, we should get the action to find out whether template is exist or not
        if ($entry instanceof Model)
        {
            $basePath = $request->getPathInfo();
            $id = $entry->getId();
            $explodedBasePath = explode('/', $basePath);
            $cleanPath = $explodedBasePath;

            unset($cleanPath[0]);

            $resourceUri = '/' . reset($cleanPath) . '/' . $id;
            $explodedResourceUri = explode('/', $resourceUri);

            $actionPath = array_diff($explodedBasePath, $explodedResourceUri);

            // Extension Priority
            // 1) *.blade.php
            // 2) *.php
            // 3) [noExtension]
            $files = array(
                reset($this->viewPaths) . '/' . reset($cleanPath) . '/' . implode('/', $actionPath) . '.blade.php',
                reset($this->viewPaths) . '/' . reset($cleanPath) . '/' . implode('/', $actionPath) . '.php',
                reset($this->viewPaths) . '/' . reset($cleanPath) . '/' . implode('/', $actionPath)
            );

            // Search the file, if it does exist, break the loop
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $retVal = reset($cleanPath) . '/' . implode('/', $actionPath);
                    break;
                }
            }

        }

        return $retVal;
    }
}
