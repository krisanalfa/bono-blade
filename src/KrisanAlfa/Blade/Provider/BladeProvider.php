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
     * Initialize the provider
     *
     * @return void
     */
    public function initialize()
    {
        $app       = App::getInstance();
        $config    = $app->config('bono.blade');
        $viewPath  = isset($config['templates.path']) ? $config['templates.path'] : $app->config('app.templates.path');
        $cachePath = isset($config['cache.path']) ? $config['cache.path'] : '../cache';
        $layout    = isset($config['layout']) ? $config['layout'] : 'layout';

        $this->makeCachePath($cachePath);

        $app->config('view', new BonoBlade($viewPath, $cachePath, $layout));
    }

    protected function makeCachePath($cachePath)
    {
        // If cache path is not exist and directory is writable, create new cache path
        if (! is_dir($cachePath)) {
            if (is_writable(dirname($cachePath))) {
                mkdir($cachePath, 0755);
            } else {
                App::getInstance()->error(new Exception("Cannot create folder in " . dirname($cachePath), 1));
            }
        }
    }
}
