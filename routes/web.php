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
    \Illuminate\Support\Facades\Log::info('3333');
    dispatch(new \App\Jobs\TestOne());
    dispatch(new \App\Jobs\TestTwo());
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
