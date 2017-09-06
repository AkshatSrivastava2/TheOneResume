<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('register','RegisterController@register');

Route::post('login','LoginController@login');

Route::post('refresh','LoginController@refresh');

Route::middleware('auth:api')->group(function() {

	Route::get('profile/{credentials}','ProfileController@index');

	Route::post('profile/store','ProfileController@store');

	Route::get('profile/{id}/edit','ProfileController@edit');

	Route::post('profile/{id}/update','ProfileController@update');

	Route::get('profile/{id}/delete','ProfileController@destroy');
    
	Route::post('logout','LoginController@logout');
});
