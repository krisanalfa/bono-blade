<?php

namespace KrisanAlfa\Blade\Theme;

use Bono\App;

class BladeTheme extends \Bono\Theme\Theme {

    protected $extension = '.blade.php';

    public function partial($template, $data) {
        $app        = App::getInstance();
        $template   = $this->resolve($template, $app->view);
        $template   = str_replace($this->extension, '', $template);
        $template   = explode('/', $template);
        $template   = implode('.', $template);

        $Clazz = $app->config('bono.partial.view');
        $view = new $Clazz;

        $view->replace($data);
        return $view->make($template, $data);
    }
}
