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

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('/beanstalkd', function () {
    // php artisan queue:work beanstalkd
    dispatch((new \App\Jobs\TestOne())->onConnection('beanstalkd'));
    dispatch((new \App\Jobs\TestTwo())->onConnection('beanstalkd'));
    return view('welcome');
});

Route::get('/database', function () {
    // php artisan queue:work database
    dispatch((new \App\Jobs\TestOne())->onConnection('database'));
    dispatch((new \App\Jobs\TestTwo())->onConnection('database'));
    return view('welcome');
});

Route::get('/redis', function () {
    // php artisan queue:work redis
    dispatch((new \App\Jobs\TestTwo())->onConnection('redis'));
    // php artisan queue:work redis
    dispatch((new \App\Jobs\TestOne())->onConnection('redis')->onQueue('test'));
    return view('welcome');
});

Route::get('mongodb-log',function (){
    \Illuminate\Support\Facades\Log::channel('mongodb')->info('test mongodb log.');
    return view('welcome');
});
