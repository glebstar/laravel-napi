<?php

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

Route::get('/bla', function () {
    return view('bla');
});

Route::post('api/register', 'Auth\AuthController@apiregister');

Route::group(['prefix' => 'api', 'middleware' => 'auth:api'], function() {
    Route::post('note/addfile/{id}', 'NoteController@addfile');
    Route::get('note/restore/{id}', 'NoteController@restore');
    Route::resource('note', 'NoteController');
});

Route::group(['middleware' => 'web'], function(){
    Route::auth();
    Route::get('/home', 'HomeController@index');
});

