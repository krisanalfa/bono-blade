#BonoBlade
Laravel Blade Template Engine for Bono PHP Framework

> **Note:** BonoBlade also use Blade templating for `partial` view

##Install
Add this line to your `composer.json` file

```
"require": {
    "krisanalfa/bono-blade": "~0.6.*"
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
    'class' => '\\KrisanAlfa\\Theme\\BladeTheme', // You can use another theme that extends from bono
),

// Bono Partial (segment of template)
'bono.partial.view' => '\\KrisanAlfa\\Blade\\BonoBlade',
```

You may use any other theme based on `BladeTheme`, such as [blade foundation](https://github.com/krisanalfa/blade-foundation).
Or you can create your own theme.

To render your
```php
use Bono\App;

$app = App::getInstance();

$app->get('/', function () use ($app) {
    $app->render('yourTemplateName', array('var' => 'value'));
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
</head>

<body>
    <div>
        @yield('content')
    </div>
</body>
</html>
```

##Le' Template

```html
<!-- myTemplate.blade.php -->
@section('title')
New Title
@endsection

@section('content')
<h1>Hello, {{ $name }}!</h1>
@endsection
```

##Renderring Template

Simply, you can render your template by call `render` function in `\Bono\App` instance.
```php
use Bono\App;

$app = App::getInstance();

$app->get('/', function () use ($app) {
    $app->view->setLayout('myLayout'); // be sure you're not adding '.blade.php' or your template will not found
    $app->render('myTemplate', array('name' => 'Krisan Alfa Timur'));
});
```

##Le' Result

```html
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Title</title>
</head>

<body>
    <div>
        <h1>Hello, Krisan Alfa Timur!</h1>
    </div>
</body>
</html>
```

##Renderring a Page Without Layout

```php
use Bono\App;

$app = App::getInstance();

$app->get('/', function () use ($app) {
    // This method is same with $app->view->partial($template, $data)
    $app->view->make('myTemplateWithoutLayout', array('name' => 'Krisan Alfa Timur'));
});
```

## Working with sections

```html
<!-- Layout, filename: myCustomLayout -->
<html>
    <body>
        @section('sidebar')
            This is the master sidebar.
        @show

        <div class="container">
            @yield('content')
        </div>
    </body>
</html>
```

```html
<!-- Template -->
@extends('myCustomLayout')

@section('sidebar')
    @parent

    <p>This is appended to the master sidebar.</p>
@stop

@section('content')
    <p>This is my body content. Appended to the container.</p>
@stop
```

Note that views which `extend` a Blade layout simply override sections from the layout. Content of the layout can be included in a child view using the `@parent` directive in a section, allowing you to append to the contents of a layout section such as a sidebar or footer.

Sometimes, such as when you are not sure if a section has been defined, you may wish to pass a default value to the `@yield` directive. You may pass the default value as the second argument:

## Including Sub-Views

```html
@include('view.name')
```

You may also pass an array of data to the included view:

```html
@include('view.name', array('some'=>'data'))
```

## Overwriting Sections

By default, sections are appended to any previous content that exists in the section. To overwrite a section entirely, you may use the `overwrite` statement:

```html
@section('test')
   one
@stop
@section('test')
   two
@stop
@yield('test')
```
The outpur is:

```html
one
```

But if you change the second `@stop` to an `@overwrite`.


```html
@section('test')
   one
@stop
@section('test')
   two
@overwrite
@yield('test')
```

Then the following is output.

```html
two
```

- `@overwrite` - End a section.
- `@stop` - Stopping Injecting Content Into a Section.
- `@show` - Yielding the Current Section in a Blade Template.
- `@append` - Stopping Injecting Content into a Section and Appending It.

## Extends Template To Be Reuseable

```html
<!-- listTemplate -->
@section('header')
My sexy header
@endsection

<div class="container">
    @section('body')
        {{-- some other controll structure to make your page happens --}}
    @endsection

    @section('action')
        <div class="action">
            <button>Edit</button>
            <button>Update</button>
        </div>
    @endsection
</div>

@section('footer')
My shiny footer
@endsection
```

```html
<!-- Another template that extends listTemplate -->

@extends('listTemplate')

@section('body')
    {{-- some other controll structure to make your page happens --}}
    {{-- some kind that make this page unique --}}

    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Rerum eligendi, totam velit earum assumenda optio accusantium magni est maiores ad inventore expedita nisi minus autem, porro adipisci cupiditate in iure!</p>

    <div class="blue">
        Some bluish content
    </div>
@overwrite
```

Based on this case, your `body` section will be overriden by `lorem ipsum` and `bluish content`.

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

Now you can use `@dateTime($dateValue)` to get your datetime value.

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

Now you can use `@pre` and `@endpre` whenever you want to `print_r()` your value. Just like this:

```html
@pre
print_r($myPrettyPrintVariable)
@endpre
```

## Setting the Content Tags Blade Uses

You know that blade uses `{{` and `}}` to specify content to be output, but this conflicts with Mustache or some other library you're using.
If you want to use other tags, you can use `setContentTags` method. Let's say you want to use `[%` and `%]` for your tags.

```php
use Bono\App;

$app = App::getInstance();

$app->view->setContentTags('[%', '%]');
```

Then your template can contain code like.

```html
The value of $variable is [% $variable %].
```

You can also pass a third argument as `true` to indicate you're setting the tags to escape content.

```php
use Bono\App;

$app = App::getInstance();

$app->view->setContentTags('[%', '%]', true);
```

Then instad of using `{{{` and `}}}` you can use `[-%` and `%-]`.

```html
The HTML tags inside this value would be escaped [%- $variable -%].
```

> **Note:** You must call `setContentTags` method before using view. The best options is: make a `Provider` that preparing all of your Blade customization.
