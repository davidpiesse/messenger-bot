<?php

use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

//Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');

Route::get('webhook', ['as' => 'webhook.get' , 'uses' =>'WebhookController@get']);
Route::post('webhook', ['as' => 'webhook.post' , 'uses' =>'WebhookController@post']);



