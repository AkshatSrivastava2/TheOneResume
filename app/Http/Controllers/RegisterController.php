<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Client;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
	private $client;

    //function to retrieve the client credentials
    public function getClient($client_id)
    {
        //dynamically retrieving the client_id and secret
        $this->client=Client::find($client_id);
    }

    public function register(Request $request)
    {
        //validating the input request
    	$this->validate($request,[

    		'name'=>'required',
    		'email'=>'required|email|unique:users,email',
    		'password'=>'required|min:6|confirmed',

    	]);

        //dynamically retrieving of the clients
        $this->getClient(request('client_id'));
        
        // creating the new user
    	$user = User::create([
    		'name'=>request('name'),
    		'email'=>request('email'),
    		'password'=>Hash::make($request->password),
    	]);

        //creating the password grant type access token
    	$params=[
    		'grant_type'=>'password',
    		'client_id'=>$this->client->id,
    		'client_secret'=>$this->client->secret,
    		'username'=>request('email'),
    		'password'=>request('password'),
    		'scope'=>'*'
    	];

    	$request->request->add($params);

        //creating a route for dispatching the token
    	$proxy=Request::create('oauth/token','POST');

        //dispatching the route request
    	return Route::dispatch($proxy);

    	//dd($request->all());
    }
}
