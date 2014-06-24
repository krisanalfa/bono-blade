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
$app->get('/', function () use ($app) {
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

use Bono\App;

$app = App::getInstance();

$app->get('/', function () use ($app) {
    $app->view->setLayout('myCustomLayout');
});
```

##Renderring a Page Without Layout

```php
use Bono\App;

$app = App::getInstance();

$app->get('/', function () use ($app) {
    $app->view->make('templatez', array('name' => 'Krisan Alfa Timur'));
});
```

## Other Blade Control Structures

#### Echoing Data

```html
Hello, {{{ $name }}}.

The current UNIX timestamp is {{{ time() }}}.
```

#### Echoing Data After Checking For Existence

Sometimes you may wish to echo a variable, but you aren't sure if the variable has been set. Basically, you want to do this:

```html
{{{ isset($name) ? $name : 'Default' }}}
```

However, instead of writing a ternary statement, Blade allows you to use the following convenient short-cut:

```html
{{{ $name or 'Default' }}}
```

#### Displaying Raw Text With Curly Braces

If you need to display a string that is wrapped in curly braces, you may escape the Blade behavior by prefixing your text with an `@` symbol:

```html
@{{ This will not be processed by Blade }}
```

Of course, all user supplied data should be escaped or purified. To escape the output, you may use the triple curly brace syntax:

```html
Hello, {{{ $name }}}.
```

If you don't want the data to be escaped, you may use double curly-braces:

```html
Hello, {{ $name }}.
```

> **Note:** Be very careful when echoing content that is supplied by users of your application. Always use the triple curly brace syntax to escape any HTML entities in the content.

#### If Statements

```php
@if (count($records) === 1)
    I have one record!
@elseif (count($records) > 1)
    I have multiple records!
@else
    I don't have any records!
@endif

@unless (App::getInstance()->auth->check())
    You are not signed in.
@endunless
```

#### Loops

```php
@for ($i = 0; $i < 10; $i++)
    The current value is {{ $i }}
@endfor

@foreach ($users as $user)
    <p>This is user {{ $user->id }}</p>
@endforeach

@while (true)
    <p>I'm looping forever.</p>
@endwhile
```

#### Including Sub-Views

```php
@include('view.name')
```

You may also pass an array of data to the included view:

```php
@include('view.name', array('some'=>'data'))
```

#### Overwriting Sections

By default, sections are appended to any previous content that exists in the section. To overwrite a section entirely, you may use the `overwrite` statement:

```php
@extends('list.item.container')

@section('list.item.content')
    <p>This is an item of type {{ $item->type }}</p>
@overwrite
```

#### Comments

```php
{{-- This comment will not be in the rendered HTML --}}
```

##Extending Blade

```php
use Bono\App;

$app = App::getInstance();

$app->view->extend(function($view, $compiler) {
    $pattern = $compiler->createMatcher('datetime');

    return preg_replace($pattern, '$1<?php echo $2->format("m/d/Y H:i:s"); ?>', $view);
});
```

Now you can use `{{ @dateTime($dateValue) }}` to get your datetime value.

The `createPlainMatcher` method is used for directives with no arguments like `@endif` and `@stop`, while `createMatcher` is used for directives with arguments.

```php
use Bono\App;

$app = App::getInstance();

$app->view->extend(function($view, $compiler) {
    $pattern = $compiler->createPlainMatcher('pre');

    return preg_replace($pattern, '<pre>', $view);
});

$app->view->extend(function($view, $compiler) {
    $pattern = $compiler->createPlainMatcher('endpre');

    return preg_replace($pattern, '</pre>', $view);
});
```

Now you can use `@pre` and `@endpre` whenever you want to `print_r()` your value.

##Read More
For more information about Blade Templating, read [this](http://laravel.com/docs/templates#blade-templating).
