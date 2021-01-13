<!doctype html>
<html class="fixed">
    <head>
        <meta charset="UTF-8">
        <title>@yield('title')</title>
        <meta name="keywords" content="HTML5 Admin Template" />
        <meta name="description" content="JSOFT Admin - Responsive HTML5 Template">
        <meta name="author" content="JSOFT.net">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <link rel="icon" type="image/png" />
        <link rel="stylesheet" href="{{ URL::asset('vendors/mdi/css/materialdesignicons.min.css') }}" />
        <link rel="stylesheet" href="{{ URL::asset('vendors/css/vendor.bundle.base.css') }}" />
        <link rel="stylesheet" href="{{ URL::asset('vendors/font-awesome/css/font-awesome.min.css') }}" />
        <link rel="stylesheet" href="{{ URL::asset('vendors/jquery-toast-plugin/jquery.toast.min.css') }}" />
        <link rel="stylesheet" href="{{ URL::asset('vendors/bootstrap-datepicker/bootstrap-datepicker.min.css') }}" />
        <link rel="stylesheet" href="{{ URL::asset('css/horizontal-layout/style.css') }}" />
        <link rel="shortcut icon" href="{{ URL::asset('images/favicon.png') }}" />
        @yield('css')
        <script src="{{ URL::asset('vendors/js/vendor.bundle.base.js') }}"></script>
        <script src="{{ URL::asset('vendors/jquery-toast-plugin/jquery.toast.min.js') }}"></script>
        <script src="{{ URL::asset('js/toastDemo.js') }}"></script>
    </head>
    <body>
        <div id="main-body">
        @yield('content')
        <!-- endinject -->
        <!-- Plugin js for this page-->
        <script src="{{ URL::asset('vendors/chart.js/Chart.min.js') }}"></script>
        <script src="{{ URL::asset('vendors/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
        <script src="{{ URL::asset('js/off-canvas.js') }}"></script>
        <script src="{{ URL::asset('js/hoverable-collapse.js') }}"></script>
        <script src="{{ URL::asset('js/template.js') }}"></script>
        <script src="{{ URL::asset('js/settings.js') }}"></script>
        <script src="{{ URL::asset('js/todolist.js') }}"></script>
        <!-- End plugin js for this page-->
        <!-- inject:js -->
        <script src="{{ URL::asset('js/dashboard.js') }}"></script>
        <script src="{{ URL::asset('js/todolist.js') }}"></script>
        @yield('js')
        </div>
        <div id="report" class="print-report"></div>
    </body>
</html>