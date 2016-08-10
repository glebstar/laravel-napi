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

Route::post('api/register', 'Auth\AuthController@apiregister');
Route::post('api/login', 'Auth\AuthController@apiauth');

Route::group(['prefix' => 'api', 'middleware' => 'jwt.auth'], function() {
    Route::post('note/addfile/{id}', 'NoteController@addfile');
    Route::get('note/restore/{id}', 'NoteController@restore');
    Route::resource('note', 'NoteController');
});

Route::group(['middleware' => 'web'], function(){
    Route::auth();
    Route::get('/home', 'HomeController@index');
});
