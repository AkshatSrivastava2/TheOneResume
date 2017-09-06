<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    //

    private $client;

    public function __construct()
    {
        //retrieving the client id and secret
    	$this->client=Client::find(2);
    }

    public function login(Request $request)
    {	
        //validating the input request
    	$this->validate($request,[
    		'username'=>'required',
    		'password'=>'required',
    	]);

        $user=User::all()->where('email',request('username'));

        if($user->count()==0)
        {
            return response()->json(['message'=>'No Such User Exist'],401);
        }

        $hashedPassword=$user[0]->password;

        if(!Hash::check(request('password'),$hashedPassword))
            return response()->json(['message'=>'No Such Password Exist'],401);

        //adding the parameter for password grant type request
    	$params=[
    		'grant_type'=>'password',
    		'client_id'=>$this->client->id,
    		'client_secret'=>$this->client->secret,
    		'username'=>request('username'),
    		'password'=>request('password'),
    		'scope'=>'*'
    	];

    	$request->request->add($params);

        //creating a route for the dispatching the request
    	$proxy=Request::create('oauth/token','POST');

        //dispatching the route request
    	return Route::dispatch($proxy);
    }

    public function refresh(Request $request)
    {
        //validating the refresh token
    	$this->validate($request,[
    		'refresh_token'=>'required',
    	]);

        //adding parameters for the refresh token grant type
    	$params=[
    		'grant_type'=>'refresh_token',
    		'client_id'=>$this->client->id,
    		'client_secret'=>$this->client->secret,
    		'username'=>request('username'),
    		'password'=>request('password'),
    	];

    	$request->request->add($params);

        //creating a route request for dispatching the new token
    	$proxy=Request::create('oauth/token','POST');

        //dispatching the route request
    	return Route::dispatch($proxy);
    }

    public function logout(Request $request)
    {
        //getting the access token of the corresponding user
    	$accessToken=Auth::user()->token();

        //revoking the token 
    	DB::table('oauth_refresh_tokens')->where('access_token_id',$accessToken)->update(['revoked'=>true]);

    	$accessToken->revoke();

        //returning the response 
    	return response()->json([],204);
    }

}
