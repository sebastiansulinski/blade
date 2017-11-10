# Blade
Package to allow you use blade templates outside of Laravel.

[![Build Status](https://travis-ci.org/sebastiansulinski/blade.svg?branch=master)](https://travis-ci.org/sebastiansulinski/blade)

### Usage instructions

Blade constructor takes 4 arguments, 2 of which are optional:

```
$viewPaths: // either a string or array of paths where your views will be fetched from
$cachePath: // string representing the path to the cache directory (to store cached version of the views)
Container $app = null: // instance of the Illuminate\Container\Container (optional)
Dispatcher $events = null: // instance of the Illuminate\Events\Dispatcher (optional)
```

With new instance of the Blade class you can call the `view()` methods the same way as from within Laravel using [view()](https://laravel.com/docs/master/views) helper.

```php
$blade = new Blade(
    realpath(__DIR__ . '/../resources/views'),
    realpath(__DIR__ . '/../resources/cache')
);
```

#### Passing variables
```php
$user = User::find(1);

$blade->view('index', compact('user'));

$blade->view('index', ['user' => $user]);

$blade->view('index')->with('user', $user);
```

#### Determining if a view exists
```php
$blade->view()->exists('test');
```

#### Sharing data with all views
```php
$blade->share('user', $user);
```

#### View composers
```php
$blade->view()->composer('dashboard', function(View $view) {

    $user = new stdClass;
    $user->name = 'Martin';

    $view->with('user', $user);

});

$blade->view('dashboard');
// has instance of $user available
```

#### Blade vew template

Use blade view templates the same way as with [Laravel](https://laravel.com/docs/master/blade)

```php
// index.blade.php

@extends('template.layout')

@section('content')

<h1>Hallo {{ $user->name }}</h1>

@endsection
```

#### Example

```php
// /public/index.php

$blade = new Blade(
    realpath(__DIR__ . '/../resources/views'),
    realpath(__DIR__ . '/../resources/cache')
);

$user = User::find(1);

echo $blade->view('pages.index', compact('user'));


// /resources/views/template/layout.blade.php

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Title</title>
</head>
<body>

<div class="row">

    <div class="column">

        @yield('content')

    </div>

</div>

</body>
</html>


// /resources/views/pages/index.blade.php

@extends('template.layout')

@section('content')

<h1>Hallo {{ $user->name }}</h1>

@endsection
```