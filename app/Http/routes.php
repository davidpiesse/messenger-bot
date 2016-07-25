<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');

Route::get('webhook', ['as' => 'webhook.get' , 'uses' =>'WebhookController@get']);
Route::get('test', ['as' => 'webhook.test' , 'uses' =>'WebhookController@test']);
Route::post('webhook', ['as' => 'webhook.post' , 'uses' =>'WebhookController@post']);



