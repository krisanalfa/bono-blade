<?php namespace KrisanAlfa\Blade;

use Bono\App;
use Closure;
use Exception;
use ErrorException;
use InvalidArgumentException;
use Illuminate\Container\Container;
use Illuminate\View\View as BladeView;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Environment;
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
 * @link      https://github.com/krisanalfa/bonoblade
 */
class BonoBlade extends View
{
    /**
     * Array containg paths where to look for blade files
     *
     * @var array
     */
    protected $viewPaths = array();

    /**
     * Location where to store cached views
     *
     * @var string
     */
    protected $cachePath = '';

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
    * @param array  $viewPaths  The path where your template resides
    * @param string $cachePath  The path where you want to store the view cache
    * @param string $layoutName The main layout you want to use
    *
    * @return KrisanAlfa\Blade\BonoBlade
    */
    public function __construct(array $viewPaths, $cachePath, $layoutName)
    {
        parent::__construct();

        $this->app       = App::getInstance();

        $this->container = new Container();

        $viewPaths       = $this->viewPaths = $this->resolvePath($viewPaths);

        $this->container->bindShared('view.paths', function () use ($viewPaths) {
            return $viewPaths;
        });

        $this->cachePath = $cachePath;

        $this->container->bindShared('cache.path', function () use ($cachePath) {
            return $cachePath;
        });

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

            $paths[$key] = $path;
        }

        return $paths;
    }

    /**
    * Register the filesystem for Blade ecosystem
    *
    * @return void
    */
    protected function registerFilesystem()
    {
        $this->container->bindShared('files', function () {
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
        $this->container->bindShared('events', function () {
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
        $mySelf = $this;

        $this->container->bindShared('view.engine.resolver', function ($app) use ($mySelf) {
            $resolver = new EngineResolver();

            $mySelf->registerPhpEngine($resolver);
            $mySelf->registerBladeEngine($resolver);

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
        $resolver->register('php', function () {
            return new PhpEngine();
        });
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
        $mySelf    = $this;
        $container = $this->container;

        // The Compiler engine requires an instance of the CompilerInterface, which in
        // this case will be the Blade compiler, so we'll first create the compiler
        // instance to pass into the engine so it can compile the views properly.
        $this->container->bindShared('blade.compiler', function ($container) use ($mySelf) {
            return new BladeCompiler($container['files'], $container['cache.path']);
        });

        // Register the blade view file finder to resolve to template
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
        $container = $this->container;

        $this->container->bindShared('view.finder', function ($app) use ($container) {
            return new FileViewFinder($app['files'], $container['view.paths']);
        });
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
        $resolver = $this->container['view.engine.resolver'];
        $finder   = $this->container['view.finder'];
        $events   = $this->container['events'];
        $env      = new Environment($resolver, $finder, $events);

        // We will also set the container instance on this view environment since the
        // view composers may be classes registered in the container, which allows
        // for great testable, flexible composers for the application developer.
        $env->setContainer($this->container);

        return $env;
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

        if ($view instanceof BladeView) {
            try {
                return $view->render();
            } catch (Exception $e) {
                $this->app->error($e);
            } catch (ErrorException $e) {
                $this->app->error($e);
            }
        }
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
        $data        = array_merge_recursive($this->all(), $data);
        $template    = $this->resolve(str_replace('/', '.', $template));
        $view        = null;
        $data['app'] = $this->app;

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
    public function all()
    {
        $data = parent::all();

        if (f('controller')) {
            $data = array_merge($data, f('controller')->getData());
        }

        return $data;
    }
}
