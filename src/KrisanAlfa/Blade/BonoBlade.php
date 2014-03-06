<?php

namespace KrisanAlfa\Blade;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Environment;

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
     * @param   string $template The path to the Blade template, relative to the Blade templates directory.
     * @param   array $data
     * @return  void
     */
    public function render($template, $data = array())
    {
        $template = $this->parsePath($template);

        $data = array_merge_recursive($this->all(), $data);

        if (! is_null($this->layout)) {
            try {
                $this->layout->content = $this->view()->make($template, $data);
            } catch (Exception $e) {
                throw $e;
            }

            echo $this->layout;
            exit();
        }

        echo $this->view()->make($template, $data);
        exit();
    }

    protected function parsePath($path) {
        $explodedPath = explode('/', $path);

        if (count($explodedPath) > 1)
        {
            return 'shared.' . $explodedPath[1];
        }
        return $path;
    }
}
