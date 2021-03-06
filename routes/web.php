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
    \Illuminate\Support\Facades\Log::info('CustomizeFormatter');
    \Illuminate\Support\Facades\Log::channel('daily')->info('CustomizeFormatter');
    \Illuminate\Support\Facades\Log::channel('custom')->info('custom');
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

Route::get('mongodb-log', function () {
    \Illuminate\Support\Facades\Log::channel('mongodb')->info('test mongodb log.');
    return view('welcome');
});

Route::get('elastic-search-log', function () {
    \Illuminate\Support\Facades\Log::channel('elasticsearch')->info('test elasticsearch info log.');
    \Illuminate\Support\Facades\Log::channel('elasticsearch')->error('test elasticsearch error log.');
    return view('welcome');
});


Route::get('scout', function () {
    dump(\App\User::search('bo')->first());
    var_dump(\App\User::search('bo')->first());
});

Route::get('snow-flake', function (){
    $id = app('snowflake')->id();
    dd($id, app('snowflake')->parseId($id));
});