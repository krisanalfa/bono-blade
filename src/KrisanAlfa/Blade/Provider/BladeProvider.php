<?php

namespace KrisanAlfa\Blade\Provider;

class BladeProvider extends \Bono\Provider\Provider {
    /**
     * Initialize the provider
     */
    public function initialize() {
        $config    = $this->app->config('bono.blade');
        $viewPath  = $config['templates'];
        $cachePath = $config['cache'];
        $layout    = @$config['layout'];

        $this->app->config('view', new \KrisanAlfa\Blade\BonoBlade($viewPath, $cachePath, $layout));
    }
}
