<?php namespace KrisanAlfa\Blade\Provider;

use KrisanAlfa\Blade\BonoBlade;
use Bono\App;
use Exception;
use Bono\Provider\Provider;

/**
 * A Provider to replace use BonoBlade view engine instead of using \Slim\View
 *
 * @category  View
 * @package   Bono
 * @author    Krisan Alfa Timur <krisan47@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @license   https://raw.github.com/xinix-technology/bono/master/LICENSE MIT
 * @link      https://github.com/krisanalfa/bonoblade
 */
class BladeProvider extends Provider
{
    /**
     * Bono Application instance
     */
    protected $app = null;

    /**
     * Configuration
     */
    protected $config = array();
    /**
     * Initialize the provider
     *
     * @return void
     */
    public function initialize()
    {
        $app    = $this->app    = App::getInstance();
        $config = $this->config = $app->config('bono.blade');

        $this->setViewPaths();
        $this->setCachePath();
        $this->setLayout();

        $app->config('view', new BonoBlade($this->viewPaths, $this->cachePath, $this->layout));
    }

    /**
     * Create our cachePath for Blade Compiler
     *
     * @throw Exception When we cannot create cache path, and the cache path doesn't exist
     *
     * @return void
     */
    protected function makeCachePath()
    {
        // If cache path is not exist and directory is writable, create new cache path
        if (! is_dir($this->cachePath)) {
            if (is_writable(dirname($this->cachePath))) {
                mkdir($this->cachePath, 0755);
            } else {
                $this->app->error(new Exception("Cannot create folder in " . dirname($this->cachePath), 1));
            }
        }
    }

    /**
     * Set view paths, where template and other view component resides
     *
     * @return void
     */
    protected function setViewPaths()
    {
        $this->viewPaths = null;

        if (isset($this->config['templates.path'])) {
            $this->viewPaths = $this->config['templates.path'];
        } else {
            $this->viewPaths = $this->app->config('app.templates.path');
        }
    }

    /**
     * Set and create our cache path for optimizing blade compiling
     *
     * @return void
     */
    protected function setCachePath()
    {
        $this->cachePath = null;

        if (isset($this->config['cache.path'])) {
            $this->cachePath = $config['cache.path'];
        } else {
            $this->cachePath = '../cache';
        }

        $this->makeCachePath();
    }

    /**
     * Set our basic layout
     *
     * @return void
     */
    protected function setLayout()
    {
        $this->layout = null;

        if (isset($this->config['layout'])) {
            $this->layout = $this->config['layout'];
        } else {
            $this->layout = 'layout';
        }
    }
}
