<!doctype html>
<html class="h-100">
<head>
    @include('layout.includes-basic.head')
</head>
<body class="d-flex flex-column h-100">

@include('layout.includes-basic.topmenu')

@include('layout.includes-basic.messages')

<div class="container my-2">
    @yield('content')
</div>

@include('layout.includes-basic.footer')

@include('layout.includes-basic.addons')

@include('layout.includes-basic.scripts')

</body>
</html>
