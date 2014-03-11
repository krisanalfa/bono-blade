#BonoBlade
Laravel Blade Template Engine for Bono PHP Framework

##How to use
Add these lines to your configuration file
```php
'bono.providers' => array(
    '\\KrisanAlfa\\Blade\\Provider\\BladeProvider'
),

'bono.blade' => array(
    'templates' => array('../templates'), // The template directories
    'cache' => '../cache',                // The cache directory
    'layout' => 'layout'                  // Leave the third argument empty if you won't use layouting
),
```

And call that function
```php
$app->get('/', function () use ($app) {
    $app->view->render('templatez', array('name' => 'Krisan Alfa Timur'));
});
```

##Le' Layout

```html
<!-- layoutz.blade.php -->
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Devel</title>

    <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />

    <link type="image/x-icon" href="{{ URL::base('favicon.ico') }}" rel="Shortcut icon" />

    <link rel="stylesheet" href="{{ URL::base('css/style.css') }}">
</head>

<body>
    <div style="padding-top: 60px; margin: 0 5px;">
        @yield('content')
    </div>

    <script type="text/javascript" src="{{ URL::base('js/jquery.js') }}"></script>
    <script type="text/javascript" src="{{ URL::base('js/asset.js') }}"></script>
</body>
</html>
```

##Le' Template

```html
<!-- templatez.blade.php -->
@section('content')
<h1>Hello {{ $name }}!</h1>
@endsection
```

##Read More
For more information about Blade Templating, read [this](http://laravel.com/docs/templates#blade-templating).
