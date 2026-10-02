@props(['name', 'data' => []])

@foreach (config('hooks.'.$name, []) as $view)
    @include($view, $data)
@endforeach
