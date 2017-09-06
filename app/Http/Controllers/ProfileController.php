<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Profile;
use App\Company;
use App\Education;

class ProfileController extends Controller
{
    //
    private $profile;

    public function updateProfile($updateProfile,$request)
    {
        //function to update profile
        $updateProfile->email=$request['email'];

        $updateProfile->name=$request['name'];

        $updateProfile->mobile=$request['mobile'];

        $updateProfile->headline=$request['headline'];

        $updateProfile->profile_url=$request['profile_url'];

        $updateProfile->job_title=$request['job_title'];

        $updateProfile->publicProfileUrl=$request['publicProfileUrl'];

        $updateProfile->summary=$request['summary'];

        return $updateProfile->save();
    }

    public function setProfile($request)
    {
        //creating a new instance of Profile type
        $this->profile=new Profile;

        //saving the details into the database
        $this->profile->email=$request['email'];

        $this->profile->name=$request['name'];

        $this->profile->mobile=$request['mobile'];

        $this->profile->headline=$request['headline'];

        $this->profile->profile_url=$request['profile_url'];

        $this->profile->job_title=$request['job_title'];

        $this->profile->publicProfileUrl=$request['publicProfileUrl'];

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


    public function index($credentials)
    {
        //retrieving the data from linkedIn
        $client=new \GuzzleHttp\Client;

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
            return response()->json(['message'=>'Could not retrieve data'],204)->header('Content-Type','application/json');
        }
        else
        {
            //retrieving the data of the data received 
            $data=$response->getBody();

            $data=json_decode($data,true);

            //returning the data with status code 200
            return response()->json(['message'=>$data],200)->header('Content-Type','application/json');
        }

    }

    public function store(Request $request)
    {        
        //checking for duplicate email_id and user id

        $emailExist=Profile::all()
                        ->where('email',$request->email);

        $userIdExist=Profile::all() ->where('user_id',\Auth::user()->id);

        if(!$emailExist->isEmpty())
        {
            return response()->json(['message'=>'Duplicate email address'],200)->header('Content-Type','application/json');
        }

        if(!$userIdExist->isEmpty())
        {
            return response()->json(['message'=>'Duplicate User ID'],200)->header('Content-Type','application/json');
        }

        $data=$request->getContent();

        $request=json_decode($data,true);

        //setting up profile,education and company details

        $this->setProfile($request);

        $companyResponse=$this->setCompany($request['values'][0]['company']);

        $educationResponse=$this->setEducation($request['values'][1]['qualifications']);

        if($this->profile->save()&&$companyResponse&&$educationResponse)
        {
            //returning the saved successfully message with status code 201
            return response()->json(['message'=>'Saved Successfully'],201)->header('Content-Type','application/json');
        }
        else
        {
            //returning the error message with status code 403
            return response()->json(['message'=>'Could not save the data'],403)->header('Content-Type','application/json');
        }
    }

    public function edit($id)
    {
        //retrieving the data corresponding to the id
        $this->profile=Profile::find($id);

        if($this->profile==null)
        {
            //returning the no data found message with status code 404
            return response()->json(['message'=>'No Data Found'],404)->header('Content-Type','application/json');
        }
        else
        {
            $company=Company::all()
                     ->where('user_id',$this->profile->user_id);

            $education=Education::all()
                     ->where('user_id',$this->profile->user_id);

            //returning the profile details with status code 200
            return response()->json(['message'=>$this->profile,'company'=>$company,'education'=>$education],200)->header('Content-Type','application/json');
        }
    }

    public function update(Request $request,$id)
    {
        //retrieving the data corresponding to id
        $this->profile=Profile::find($id);

        if($this->profile==null)
        {
            return response()->json(['message'=>'No data found'],404)->header('Content-Type','application/json');
        }

        //checking for duplicate email_id
        $emailExist=Profile::all()->where('email',$request->email);
        
        if(!$emailExist->isEmpty())
        {
            return response()->json(['message'=>'Duplicate email address'],409)->header('Content-Type','application/json');
        }

        if(\Auth::user()->id!=$this->profile->user_id)
        {
            return response()->json(['message'=>'Unauthorised'],401)->header('Content-Type','application/json');
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
            return response()->json(['message'=>'Updated Successfully'],200)->header('Content-Type','application/json');
        }
        else
        {
            //returning error message with status code 403
            return response()->json(['message'=>'Could not retrieve data'],403)->header('Content-Type','application/json');
        }
    }

    public function destroy($id)
    {
        //retrieving the data corresponding to id
        $this->profile=Profile::find($id);

        if($this->profile==null)
        {
            //returning the no data found message with status code 404
            return response()->json(['message'=>'No data found'],404)->header('Content-Type','application/json');
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
                return response()->json(['message'=>'Successfully Deleted'],200)->header('Content-Type','application/json');
            }
            else
            {
                //returning the error message with status code 403
                return response()->json(['message'=>'Could not retrieve data'],403)->header('Content-Type','application/json');
            }
        }
    }
}
