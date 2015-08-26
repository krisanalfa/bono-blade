<?php namespace KrisanAlfa\Blade;

use Bono\App;
use Closure;
use InvalidArgumentException;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Slim\View;

/**
 * A Blade Template Engine for Bono PHP Framework
 *
 * @category  View
 * @package   Bono
 * @author    Krisan Alfa Timur <krisan47@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @license   https://raw.github.com/xinix-technology/bono/master/LICENSE MIT
 * @link      https://github.com/krisanalfa/bono-blade
 */
class BladeView extends View
{

    /**
     * A slim container for Blade ecosystem
     *
     * @var Illuminate\Container\Container
     */
    public $container;

    /**
     * Blade View instance
     *
     * @var Illuminate\View\Environment
     */
    protected $instance;

    /**
     * The main layout
     *
     * @var Illuminate\View\View
     */
    protected $layout = '';

    /**
    * Initalizer a.k.a the class constructor
    * Leave the third arguments empty if you won't use any layout
    *
    * @param array  $options  Options for BladeView
    *
    * @return KrisanAlfa\Blade\BladeView
    */
    public function __construct(array $options)
    {
        parent::__construct();

        $this->app       = App::getInstance();

        $this->container = new Container();

        $viewPaths       = $this->resolvePath($options['viewPaths']);

        $this->container->singleton('view.paths', function () use ($viewPaths) {
            return $viewPaths;
        });

        $cachePath = $options['cachePath'];

        $this->container->singleton('cache.path', function () use ($cachePath) {
            return $cachePath;
        });

        $this->registerFilesystem();

        $this->registerEvents();

        $this->registerEngineResolver();

        $this->registerViewFinder();

        $this->registerFactory();

        $this->instance = $this->container->make('view');
    }

    /**
     * Build an array for templates path directory
     *
     * @return void
     */
    protected function resolvePath(array $originalPaths)
    {
        $paths = array();

        foreach ($originalPaths as $key => $path) {
            if (count(explode('templates', $path)) == 1) {
                $path = $path . DIRECTORY_SEPARATOR . 'templates';
            }

            if (realpath($path)) {
                $paths[$key] = realpath($path);
            }

        }

        return array_unique($paths);
    }

    /**
    * Register the filesystem for Blade ecosystem
    *
    * @return void
    */
    protected function registerFilesystem()
    {
        $this->container->singleton('files', function () {
            return new Filesystem();
        });
    }

    /**
    * Register the view event
    *
    * @return void
    */
    protected function registerEvents()
    {
        $this->container->singleton('events', function () {
            return new Dispatcher();
        });
    }

    /**
     * Register the engine resolver instance.
     *
     * @return void
     */
    protected function registerEngineResolver()
    {
        $this->container->singleton('view.engine.resolver', function () {
            $resolver = new EngineResolver;
            // Next we will register the various engines with the resolver so that the
            // environment can resolve the engines it needs for various views based
            // on the extension of view files. We call a method for each engines.
            foreach (['php', 'blade'] as $engine) {
                $this->{'register'.ucfirst($engine).'Engine'}($resolver);
            }

            return $resolver;
        });
    }

    /**
     * Register the PHP engine implementation.
     *
     * @param \Illuminate\View\Engines\EngineResolver $resolver Engine Resolver
     *
     * @return void
     */
    protected function registerPhpEngine(EngineResolver $resolver)
    {
        $resolver->register('php', function () { return new PhpEngine; });
    }

    /**
     * Register the Blade engine implementation.
     *
     * @param \Illuminate\View\Engines\EngineResolver $resolver Engine Resolver
     *
     * @return void
     */
    protected function registerBladeEngine(EngineResolver $resolver)
    {
        $container = $this->container;

        // The Compiler engine requires an instance of the CompilerInterface, which in
        // this case will be the Blade compiler, so we'll first create the compiler
        // instance to pass into the engine so it can compile the views properly.
        $container->singleton('blade.compiler', function ($container) {
            return new BladeCompiler($container['files'], $container['cache.path']);
        });

        $resolver->register('blade', function () use ($container) {
            return new CompilerEngine($container['blade.compiler'], $container['files']);
        });
    }

    /**
     * Register the view finder implementation.
     *
     * @return void
     */
    protected function registerViewFinder()
    {
        $this->container->bind('view.finder', function ($container) {
            return new FileViewFinder($container['files'], $container['view.paths']);
        });
    }

    /**
     * Register the view environment.
     *
     * @return Illuminate\View\Factory
     */
    protected function registerFactory()
    {
        $this->container->singleton('view', function ($container) {
            // Next we need to grab the engine resolver instance that will be used by the
            // environment. The resolver will be used by an environment to get each of
            // the various engine implementations such as plain PHP or Blade engine.
            $resolver = $container['view.engine.resolver'];
            $finder = $container['view.finder'];

            $env = new Factory($resolver, $finder, $container['events']);
            // We will also set the container instance on this view environment since the
            // view composers may be classes registered in the container, which allows
            // for great testable, flexible composers for the application developer.
            $env->setContainer($container);
            $env->share('app', $container);

            return $env;
        });
    }

    /**
     * Extend the compiler
     *
     * @param Closure $function callback to the Blade::extend()
     *
     * @return void
     */
    public function extend(Closure $function)
    {
        return $this->container['blade.compiler']->extend($function);
    }

    /**
     * Sets the content tags used for the compiler.
     *
     * @param  string $openTag
     * @param  string $closeTag
     * @param  bool   $escaped
     * @return void
     */
    public function setContentTags($openTag, $closeTag, $escaped = false)
    {
        return $this->container['blade.compiler']->setContentTags($openTag, $closeTag, $escaped);
    }

    /**
     * Get the compiler
     *
     * @return BladeCompiler
     */
    public function getCompiler()
    {
        return $this->container['blade.compiler'];
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
    public function setLayout($layout, array $data = array())
    {
        if (is_null($layout)) {
            $this->layout = null;

            return;
        }

        $this->layout = $this->make($this->resolve($layout), $data);
    }

    /**
     * This method echoes the rendered template to the current output buffer
     *
     * @param string $template Pathname of template file relative to templates directory
     *
     * @return void
     */
    public function display($template, $data = array())
    {
        echo $this->fetch($template, $data);
    }

    /**
     * Return the contents of a rendered template file
     *
     * @var string $template The template pathname, relative to the template base directory
     *
     * @return Illuminate\View\View
     */
    public function fetch($template, $data = array())
    {
        $view = $this->render($template, $data);

        return ($view) ? $view->render() : '';
    }

    /**
     * This method will output the rendered template content
     *
     * @param string $template The path to the Blade template, relative to the Blade templates directory.
     *
     * @return Illuminate\View\View
     */
    protected function render($template, $data = array())
    {
        $view        = null;
        $data        = array_merge_recursive($this->all(), $data);
        $data['app'] = $this->app;
        $template    = $this->resolve($template);


        if (! $template) {
            return;
        }

        if (! $this->layout) {
            $view = $this->make($template, $data);
        } else {
            $view = $this->layout->nest('content', $template, $data);
        }

        return $view;
    }

    /**
    * This method will try to find the existing of template file
    *
    * @param string $path The relative template path
    *
    * @return string
    * @throws InvalidArgumentException If we cannot find view
    */
    public function resolve($path)
    {
        $path = str_replace('/', '.', preg_replace('/\/:\w+/', '', $path));

        $finder = $this->container['view.finder'];

        try {
            $finder->find($path);
        } catch (InvalidArgumentException $e) {
            $explodedPath = explode('.', $path);


            if ($explodedPath[0] === 'static') {
                return null;
            }

            if (count($explodedPath) > 1) {
                $explodedPath[0] = 'shared';
                $explodedPath = array('shared', end($explodedPath));
                try {
                    $finder->find(implode('.', $explodedPath));
                    $path = implode('.', $explodedPath);
                } catch (InvalidArgumentException $e) {
                    $this->app->error($e);
                }
            }
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

        try {
            return call_user_func_array(array($view, $method), $args);
        } catch (RuntimeException $e) {
            $this->app->error($e);
        }
    }

    /**
     * Override all method on Slim\View
     *
     * @return array
     */
    // public function all()
    // {
    //     $data = parent::all();

    //     if (f('controller')) {
    //         // var_dump($this->app->response->data(), $this->app->response->data());
    //         // exit;
    //         $data = array_merge($data, $this->app->response->data());
    //     }

    //     return $data;
    // }
}
