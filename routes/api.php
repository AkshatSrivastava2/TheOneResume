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

	Route::get('profile','LinkedInController@index');

	Route::post('profile/store','LinkedInController@store');

	Route::get('profile/{id}/edit','LinkedInController@edit');

	Route::post('profile/{id}/update','LinkedInController@update');

	Route::get('profile/{id}/delete','LinkedInController@destroy');
    
	Route::post('logout','LoginController@logout');

});
Route::get('user/linkedin', 'LinkedInController@makeRequest');

Route::get('oauth2/linkedin','LinkedInController@getRequest');