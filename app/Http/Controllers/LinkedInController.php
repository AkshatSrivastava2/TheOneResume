<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Exception\ClientException;
use App\Profile;
use App\Company;
use App\User;
use App\Education;

class LinkedInController extends Controller
{
    //
    private $profile;

    public function updateProfile($updateProfile,$request)
    {
        //function to update profile
        
        $updateProfile->currentlyWorkingAt=$request['currentlyWorkingAt'];

        $updateProfile->profileImageUrl=$request['profileImageUrl'];

        $updateProfile->currentlyWorkingAs=$request['currentlyWorkingAs'];

        $updateProfile->profileUrl=$request['profileUrl'];

        $updateProfile->summary=$request['summary'];

        return $updateProfile->save();
    }

    public function setProfile($request)
    {
        //creating a new instance of Profile type
        $this->profile=new Profile;

        //saving the details into the database
        
        $this->profile->currentlyWorkingAt=$request['currentlyWorkingAt'];

        $this->profile->profileImageUrl=$request['profileImageUrl'];

        $this->profile->currentlyWorkingAs=$request['currentlyWorkingAs'];

        $this->profile->profileUrl=$request['profileUrl'];

        $this->profile->summary=$request['summary'];

        $this->profile->user_id=\Auth::user()->id;
    }

    public function setCompany($request)
    {
        for($i=0;$i<count($request);$i++)
        {
            //creating new instance of company
            $company=new Company;

            //storing the data into the company credentials
            $company->company_name=$request[$i]['name'];

            $company->company_address=$request[$i]['address'];

            $company->title=$request[$i]['title'];

            $company->started_on=$request[$i]['start'];

            $company->ended_on=$request[$i]['end'];

            $company->user_id=$this->profile->user_id;

            if(!$company->save())
            {
                return false;
            }
        }
        return true;
    }

    public function setEducation($request)
    {
        for($i=0;$i<count($request);$i++)
        {
            //creating new instance of education
            $education=new Education;

            //set the values of education
            $education->address_name=$request[$i]['address'];

            $education->qualification=$request[$i]['qualification'];

            $education->yearOfPassing=$request[$i]['yearOfPassing'];

            $education->percentage=$request[$i]['percentage'];

            $education->user_id=$this->profile->user_id;

            if(!$education->save())
            {
                return false;
            }
        }
        return true;
    }


    public function makeRequest()
    {        
        $query = http_build_query([
            'client_id' => '819ki1ptazvjjl',
            'redirect_uri' => 'http://localhost:8000/api/oauth2/linkedin',
            'response_type' => 'code',
            'state'=>'DCEeFWf45A53sdfKef424',

        ]);

        return redirect('https://www.linkedin.com/oauth/v2/authorization?' . $query);
    }    
    
    public function getRequest(Request $request)
    {
        
        $http = new \GuzzleHttp\Client;
        $response = $http->post('https://www.linkedin.com/oauth/v2/accessToken', 
            [
                'form_params' => [
                                    'grant_type' => 'authorization_code',
                                    'client_id' => '819ki1ptazvjjl',
                                    'client_secret' => 'z6s2aKqGzMg2mJGq',
                                    'redirect_uri' => 'http://localhost:8000/api/oauth2/linkedin',
                                    'code' => $request->code
                                ]
            ], 
            [
                'headers' =>[
                                'Accept'     => 'application/json',
                                'Content-Type' => 'application/x-www-form-urlencoded',
                            ]
            ]);

        $body=$response->getBody();
        return $this->storeAccessTokenInCache(substr($body,17,179));
    }

    public function storeAccessTokenInCache($data)
    {   
        Cache::forever('linkedin_Oauth_token',$data);
        return response()->json(['message'=>'Success', 'code'=>'200', 'access_token'=>Cache::get('linkedin_Oauth_token')], '200');   
    }

    public function index()
    {
        //retrieving the data from linkedIn
        $this->makeRequest();
        
        try
        {
            if(Cache::has('linkedin_Oauth_token'))
            {
                $client=new \GuzzleHttp\Client;

                $credentials=Cache::get('linkedin_Oauth_token');

                $response=$client->request('GET','https://api.linkedin.com/v1/people/~:(id,num-connections,picture-url,location,headline,positions,email-address,summary,formatted-name,public-profile-url)?format=json',
                [
                    'headers'=>[
                        'Authorization'=>'Bearer '.$credentials,
                    ],
                ]);

                //retrieving the status code of the data received
                $statusCode=$response->getStatusCode();

                //checking the status of the retrieved data
                if($statusCode!=200)
                {
                //return the error message with status code 204
                return response()->json(['message'=>'Could not retrieve data','code'=>'204'],204)->header('Content-Type','application/json');
                }
                else
                {
                //retrieving the data of the data received 
                $data=$response->getBody();

                $data=json_decode($data,true);

                //returning the data with status code 200
                return response()->json(['message'=>$data,'code'=>'200'],200)->header('Content-Type','application/json');
                }
            }
            else
            {
                return response()->json(['error'=>'Unauthorised To Use LinkedIn Endpoints', 'code'=>'401'], '401');
            }

        }
        catch (ClientException $e) 
        {
            if($e->getResponse()->getStatusCode()==401)
            {
                return response()->json(['error'=>'Unauthorised To Use LinkedIn Endpoints', 'code'=>'401'], '401');               
            }
        }           
    }

    public function store(Request $request)
    {        
        try
        {
            if(Cache::has('linkedin_Oauth_token'))
            {
                $userIdExist=Profile::all()->where('user_id',\Auth::user()->id);

                if(!$userIdExist->isEmpty())
                {
                    return response()->json(['message'=>'Duplicate User ID','code'=>'404'],404)->header('Content-Type','application/json');
                }

                $data=$request->getContent();
                // dd($request);

                $request=json_decode($data,true);

                //setting up profile,education and company details

                $this->setProfile($request);

                $companyResponse=$this->setCompany($request['values'][0]['company']);

                $educationResponse=$this->setEducation($request['values'][1]['qualifications']);

                if($this->profile->save()&&$companyResponse&&$educationResponse)
                {
                    //returning the saved successfully message with status code 201
                    return response()->json(['message'=>'Saved Successfully','code'=>'201'],201)->header('Content-Type','application/json');
                }
                else
                {
                    //returning the error message with status code 403
                    return response()->json(['message'=>'Could not save the data','code'=>'403'],403)->header('Content-Type','application/json');
                }
            }
            else
            {
                return response()->json(['error'=>'Unauthorised To Use LinkedIn Endpoints', 'code'=>'401'], '401');
            }
        }
        catch (ClientException $e) 
        {
            if($e->getResponse()->getStatusCode()==401)
            {
                return response()->json(['error'=>'Unauthorised To Use LinkedIn Endpoints', 'code'=>'401'], '401');               
            }
        }      
    }

    public function edit($id)
    {
        try
        {
            if(Cache::has('linkedin_Oauth_token'))
            {

                //retrieving the data corresponding to the id
                $this->profile=Profile::find($id);

                if($this->profile==null)
                {
                    //returning the no data found message with status code 404
                    return response()->json(['message'=>'No Data Found','code'=>'404'],404)->header('Content-Type','application/json');
                }
                else
                {
                    $company=Company::all()
                        ->where('user_id',$this->profile->user_id);

                    $education=Education::all()
                        ->where('user_id',$this->profile->user_id);

                    $basic=User::all()->where('id',$this->profile->user_id);

                    //returning the profile details with status code 200
                    return response()->json(['message'=>$this->profile,'basic'=>$basic,'company'=>$company,'education'=>$education],200)->header('Content-Type','application/json');
                }
            }
            else
            {
                return response()->json(['error'=>'Unauthorised To Use LinkedIn Endpoints', 'code'=>'401'], '401');
            }
        }
        catch (ClientException $e) 
        {
            if($e->getResponse()->getStatusCode()==401)
            {
                return response()->json(['error'=>'Unauthorised To Use LinkedIn Endpoints', 'code'=>'401'], '401');               
            }
        }      
    }

    public function update(Request $request,$id)
    {
        try
        {
            if(Cache::has('linkedin_Oauth_token'))
            {

                //retrieving the data corresponding to id
                $this->profile=Profile::find($id);

                if($this->profile==null)
                {
                    return response()->json(['message'=>'No data found','code'=>'404'],404)->header('Content-Type','application/json');
                }

                if(\Auth::user()->id!=$this->profile->user_id)
                {
                    return response()->json(['message'=>'Unauthorised','code'=>'401'],401)->header('Content-Type','application/json');
                }

                $data=$request->getContent();

                $request=json_decode($data,true);

                $profileResponse=$this->updateProfile($this->profile,$request);

                $company=Company::all()->where('user_id',$this->profile->user_id);

                for($i=0;$i<$company->count();$i++)
                {
                    $company[$i]->delete();
                }

                $education=Education::all()->where('user_id',$this->profile->user_id);

                for($i=0;$i<$education->count();$i++)
                {
                    $education[$i]->delete();
                }

                $companyResponse=$this->setCompany($request['values'][0]['company']);

                $educationResponse=$this->setEducation($request['values'][1]['qualifications']);

                if($profileResponse&&$companyResponse&&$educationResponse)
                {

                    //returning updated successfully message with status code 200
                    return response()->json(['message'=>'Updated Successfully','code'=>'200'],200)->header('Content-Type','application/json');
                }
                else
                {
                    //returning error message with status code 403
                    return response()->json(['message'=>'Could not retrieve data','code'=>'403'],403)->header('Content-Type','application/json');
                }
            }
            else
            {
                return response()->json(['error'=>'Unauthorised To Use LinkedIn Endpoints', 'code'=>'401'], '401');
            }
        }
        catch (ClientException $e) 
        {
            if($e->getResponse()->getStatusCode()==401)
            {
                return response()->json(['error'=>'Unauthorised To Use LinkedIn Endpoints', 'code'=>'401'], '401');               
            }
        }      
    }

    public function destroy($id)
    {
        try
        {
            if(Cache::has('linkedin_Oauth_token'))
            {

                //retrieving the data corresponding to id
                $this->profile=Profile::find($id);

                if($this->profile==null)
                {
                    //returning the no data found message with status code 404
                    return response()->json(['message'=>'No data found','code'=>'404'],404)->header('Content-Type','application/json');
                }
                else
                {
                    if($this->profile->delete())
                    {
                        //returning successfully deleted message with status code 200
                        $company=Company::all()->where('user_id',$this->profile->user_id);

                        for($i=0;$i<$company->count();$i++)
                        {
                            $company[$i]->delete();
                        }

                        $education=Education::all()->where('user_id',$this->profile->user_id);

                        for($i=0;$i<$education->count();$i++)
                        {
                            $education[$i]->delete();
                        }
                        return response()->json(['message'=>'Successfully Deleted','code'=>'200'],200)->header('Content-Type','application/json');
                    }
                    else
                    {
                        //returning the error message with status code 403
                        return response()->json(['message'=>'Could not retrieve data','code'=>'403'],403)->header('Content-Type','application/json');
                    }
                }
            }
            else
            {
                return response()->json(['error'=>'Unauthorised To Use LinkedIn Endpoints', 'code'=>'401'], '401');
            }
        }
        catch (ClientException $e) 
        {
            if($e->getResponse()->getStatusCode()==401)
            {
                return response()->json(['error'=>'Unauthorised To Use LinkedIn Endpoints', 'code'=>'401'], '401');               
            }
        }         
    }
}
