<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/index.html', function () {
    return view('welcome');
});

Route::get('/neworder', 'OrderController@newOrder')->name('neworder')/*->middleware('auth')*/;
Route::get('/neworderajax', 'OrderController@newOrderAjax')->name('neworderajax');
Route::get('/order_details', 'OrderController@order_details')->name('orderdetails');
Route::get('/pickingorder', 'OrderController@pickingOrder')->name('pickingorder')/*->middleware('auth')*/;
Route::get('/verifyorder', 'OrderController@verifyOrder')->name('verifyorder')/*->middleware('auth')*/;
Route::get('/finishorder', 'OrderController@finishOrder')->name('finishorder')/*->middleware('auth')*/;
Route::get('/voidorder', 'OrderController@voidOrder')->name('voidorder')/*->middleware('auth')*/;
Route::get('/allorder', 'OrderController@allOrder')->name('allorder')/*->middleware('auth')*/;
Route::get('/printOrders', 'OrderController@printOrders')->name('printOrders')/*->middleware('auth')*/;

Route::post('/neworderdesc', 'OrderController@neworderDesc')->name('neworderdesc');
Route::post('/pickingorderdesc', 'OrderController@pickingorderDesc')->name('pickingorderdesc');
Route::post('/verifyorderdesc', 'OrderController@verifyorderDesc')->name('verifyorderdesc');
Route::post('/finishorderdesc', 'OrderController@finishorderDesc')->name('finishorderdesc');
Route::post('/voidorderdesc', 'OrderController@voidorderDesc')->name('voidorderdesc');
Route::post('/allorderdesc', 'OrderController@allorderDesc')->name('allorderdesc');

Route::get('/login', 'Auth\LoginController@index')->name('login');
Route::post('/loginAction', 'Auth\LoginController@loginAction')->name('loginAction');