# Blade
Package to allow you use blade templates outside of Laravel.

### Usage instructions

Blade constructor takes 4 arguments, 2 of which are optional:

```
$viewPaths: // either a string or array of paths where your views will be fetched from
$cachePath: // string representing the path to the cache directory (to store cached version of the views)
Container $container = null: // instance of the Illuminate\Container\Container (optional)
Dispatcher $events = null: // instance of the Illuminate\Events\Dispatcher (optional)
```

With new instance of the Blade class you can call the `view()` methods the same way as from within Laravel using [view()](https://laravel.com/docs/master/views) helper.

```
$blade = new Blade(
    realpath(__DIR__ . '/../resources/views'),
    realpath(__DIR__ . '/../resources/cache')
);
```

#### Passing variables
```
$user = User::find(1);

$blade->view('index', compact('user'));

$blade->view('index', ['user' => $user]);

$blade->view('index')->with('user', $user);
```

#### Determining if a view exists
```
$blade->view()->exists('test');
```

#### Sharing data with all views
```
$blade->share('user', $user);
```

#### View composers
```
$blade->view()->composer('dashboard', function(View $view) {

    $user = new stdClass;
    $user->name = 'Martin';

    $view->with('user', $user);

});

$blade->view('dashboard');
// has instance of $user available
```

Use blade view templates the same way as with [Laravel](https://laravel.com/docs/master/blade)

```
// index.blade.php

@extends('template.layout')

@section('content')

<h1>Hallo {{ $user->name }}</h1>

@endsection
```