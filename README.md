#BonoBlade
Laravel Blade Template Engine for Bono PHP Framework

**note: BonoBlade also use Blade templating for `partial` view**

##Install
Add this line to your `composer.json` file

For `0.1.10` Bono use

```
"require": {
    "krisanalfa/bono-blade": "~0.1.0"
},
```

For `dev-master` Bono use. Bono is fast developed, you must use upstream version.

```
"require": {
    "krisanalfa/bono-blade": "*"
},
```

##How to use
Add these lines to your configuration file
```php
'bono.providers' => array(
    '\\KrisanAlfa\\Blade\\Provider\\BladeProvider'
),

// This section is not required, but if you want customize the config, here's a base config
'bono.blade' => array(
    'templates.path' => array('pathToTemplatesPath'), // Default is array('../templates')
    'cache.path' => 'pathToCachePath',                // Default is '../cache'
    'layout' => 'customLayoutName',                   // Default is 'layout'
),

// Bono Themeing
'bono.theme' => array(
    'class' => '\\KrisanAlfa\\Theme\\BladeTheme',
),

// Bono Partial (segment of template)
'bono.partial.view' => '\\KrisanAlfa\\Blade\\BonoBlade',
```

And call that function
```php
$app->get('/', function () use ($app)
{
    $app->render('template', array('name' => 'Krisan Alfa Timur'));
});
```

##Le' Layout

```html
<!-- myLayout.blade.php -->
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Devel')</title>

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
<!-- template.blade.php -->
@section('title')
New Title
@endsection

@section('content')
<h1>Hello, {{ $name }}!</h1>
@endsection
```

##Le' Result

```html
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
        <h1>Hello, Krisan Alfa Timur!</h1>
    </div>

    <script type="text/javascript" src="{{ URL::base('js/jquery.js') }}"></script>
    <script type="text/javascript" src="{{ URL::base('js/asset.js') }}"></script>
</body>
</html>
```

##Using Layout

```php
$app = \Bono\App::getInstance();

$app->get('/', function () use ($app)
{
    $app->view->setLayout('myCustomLayout');
});
```

##Renderring a Page Without Layout

```php
$app = \Bono\App::getInstance();

$app->get('/', function () use ($app)
{
    $app->view->make('templatez', array('name' => 'Krisan Alfa Timur'));
});
```

##Read More
For more information about Blade Templating, read [this](http://laravel.com/docs/templates#blade-templating).
