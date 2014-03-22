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

namespace KrisanAlfa\Blade\Theme;

use Bono\App;

/**
 * A Blade Template Engine for Theme Resolver
 *
 * @author      Krisan Alfa Timur <krisan47@gmail.com>
 * @package     BonoBlade
 * @subpackage  Theme\Theme
 * @link        https://github.com/krisanalfa/bonoblade
 */
class BladeTheme extends \Bono\Theme\Theme {

    protected $extension = '.blade.php';

    public function partial($template, $data) {
        $app      = App::getInstance();

        $template = $this->resolve($template, $app->view);
        $template = str_replace($this->extension, '', $template);
        $template = explode('/', $template);
        $template = implode('.', $template);

        $Clazz    = $app->config('bono.partial.view');
        $view     = new $Clazz;

        $view->replace($data);

        $retVal = $view->make($template, $data);

        try
        {
            $retVal->__toString();
        }
        catch (\RuntimeException $e)
        {
            $app->error($e);
        }

        return $retVal;
    }
}
