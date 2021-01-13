<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DatabaseConnectionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => "sa", "PWD" => "sa@123");
        $conn = sqlsrv_connect("192.168.75.140", $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        return $conn;
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
