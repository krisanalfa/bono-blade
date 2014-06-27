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
     * Initialize the provider
     *
     * @return void
     */
    public function initialize()
    {
        $app = $this->app = App::getInstance();

        $app->config('view', new BonoBlade($this->setViewPaths(), $this->setCachePath(), $this->setLayout()));
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
        try {
            mkdir($this->cachePath, 0755);
        } catch (Exception $e) {
            $this->app->error($e);
        }
    }

    /**
     * Set view paths, where template and other view component resides
     *
     * @return void
     */
    protected function setViewPaths()
    {
        return $this->defaultConfig('templates.path', (array) $this->app->config('app.templates.path'));
    }

    /**
     * Set and create our cache path for optimizing blade compiling
     *
     * @return void
     */
    protected function setCachePath()
    {
        $cachePath = $this->defaultConfig('cache.path', '../cache');

        if (! is_dir($cachePath)) {
            $this->makeCachePath();
        }

        return $cachePath;
    }

    /**
     * Set our basic layout
     *
     * @return void
     */
    protected function setLayout()
    {
        return $this->defaultConfig('layout', 'layout');
    }

    protected function defaultConfig($key, $default)
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        } else {
            return $default;
        }
    }
}
