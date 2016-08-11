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

Route::post('api/v1/register', 'Auth\AuthController@apiregister');
Route::post('api/v1/login', 'Auth\AuthController@apiauth');

Route::group(['prefix' => 'api/v1', 'middleware' => 'jwt.auth'], function() {
    Route::post('note/addfile/{id}', 'NoteController@addfile');
    Route::get('note/restore/{id}', 'NoteController@restore');
    Route::resource('note', 'NoteController');
});

Route::group(['prefix' => 'api/v101', 'middleware' => 'jwt.auth'], function() {
    Route::post('note/addfile/{id}', 'NoteV1_0_1Controller@addfile');
    Route::get('note/restore/{id}', 'NoteV1_0_1Controller@restore');
    Route::resource('note', 'NoteV1_0_1Controller');
});

Route::group(['prefix' => 'api/v2', 'middleware' => 'jwt.auth'], function() {
    Route::post('note/addfile/{id}', 'NoteV2Controller@addfile');
    Route::get('note/restore/{id}', 'NoteV2Controller@restore');
    Route::resource('note', 'NoteV2Controller');
});

Route::group(['middleware' => 'web'], function(){
    Route::auth();
    Route::get('/home', 'HomeController@index');
});
