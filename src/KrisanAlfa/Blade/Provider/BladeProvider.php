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
namespace KrisanAlfa\Blade\Provider;

use \KrisanAlfa\Blade\BonoBlade;
use \Bono\App;

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
class BladeProvider extends \Bono\Provider\Provider
{
    /**
     * Initialize the provider
     *
     * @return void
     */
    public function initialize()
    {
        $app       = App::getInstance();
        $config    = $this->app->config('bono.blade');
        $viewPath  = @$config['templates.path'] ?: $app->config('app.templates.path');
        $cachePath = @$config['cache.path'] ?: '../cache';
        $layout    = @$config['layout'] ?: 'layout';

        // If cache path is not exist and directory is writable, create new cache path
        if (! is_dir($cachePath) and is_writable(dirname($cachePath))) {
            mkdir($cachePath, 0755);
        }

        $this->app->config('view', new BonoBlade($viewPath, $cachePath, $layout));
    }
}
