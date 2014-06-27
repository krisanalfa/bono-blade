<?php namespace KrisanAlfa\Blade;

use Bono\App;
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
use Exception;
use Slim\View;
use Closure;

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
    protected $container;

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
    public function __construct($viewPaths = array(), $cachePath = '', $layoutName = null)
    {
        parent::__construct();

        $this->app       = App::getInstance();

        $config          = $this->app->config('bono.blade');

        $this->viewPaths = (array) @$config['templates'] ?: (array) $viewPaths;

        $this->cachePath = @$config['cache'] ?: $cachePath;

        $this->container = new Container;

        $this->resolvePath();

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
     * Get protected attributes of cachePath
     *
     * @return string
     */
    public function getCachePath()
    {
        return $this->cachePath;
    }

    /**
     * Get protected attributes of viewPaths
     *
     * @return array
     */
    public function getViewPaths()
    {
        return $this->viewPaths;
    }

    /**
     * Build an array for templates path directory
     *
     * @return void
     */
    protected function resolvePath($bonoTemplatePathName = 'templates')
    {
        $paths = array_merge_recursive(App::getInstance()->theme->getBaseDirectory(), $this->viewPaths);
        $paths = $this->arrayFlatten($paths);

        foreach ($paths as $key => $path) {
            if (count(explode($bonoTemplatePathName, $path)) == 1) {
                $paths[$key] = $path . DIRECTORY_SEPARATOR . $bonoTemplatePathName;
            }
        }

        $this->viewPaths = $paths;
    }

    /**
    * Register the filesystem for Blade ecosystem
    *
    * @return void
    */
    protected function registerFilesystem()
    {
        $this->container->bindShared('files', function () {
            return new Filesystem;
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
            return new Dispatcher;
        });
    }

    /**
     * A helper to flatten array
     *
     * @param array $array The array you want to flattened
     *
     * @return array The flattened array
     */
    protected function arrayFlatten($array)
    {
        $flattenedArray = array();

        array_walk_recursive($array, function ($x) use (&$flattenedArray) {
            $flattenedArray[] = $x;
        });

        return $flattenedArray;
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
            $resolver = new EngineResolver;

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
            return new PhpEngine;
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
            $cachePath = $mySelf->getCachePath();

            return new BladeCompiler($container['files'], $cachePath);
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
        $mySelf = $this;

        $this->container->bindShared('view.finder', function ($app) use ($mySelf) {
            return new FileViewFinder($app['files'], $mySelf->getViewPaths());
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
     * @param  string  $openTag
     * @param  string  $closeTag
     * @param  bool    $escaped
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
        $app          = App::getInstance();
        $layout       = $app->theme->resolve($layout);

        if ($layout) {
            $this->layout = $this->make($layout, $data);
        }
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
                App::getInstance()->error($e);
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
        $data     = array_merge_recursive($this->all(), $data);
        $template = $this->resolve($template);
        $view     = null;

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
    */
    public function resolve($path)
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
                if (! is_null($template)) return $template;

                // Get shared template
                $template = $this->sharedTemplateIsExist($viewPath, $explodedPath);
                if (! is_null($template)) return $template;
            }
        }

        return $path;
    }

    /**
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
        $glued = implode(DIRECTORY_SEPARATOR, $explodedPath);
        $file  = realpath($viewPath . DIRECTORY_SEPARATOR . $glued . '.blade.php');

        if (is_readable($file)) {
            return implode('.', $explodedPath);
        }
    }

    /**
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
        $tail = end($explodedPath);
        $file = realpath($viewPath . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . $tail . '.blade.php');

        if (is_readable($file)) {
            return 'shared.' . $tail;
        }
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
            App::getInstance()->error($e);
        }
    }
}
