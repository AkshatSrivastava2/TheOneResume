<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    //
    protected $table='profiles';

    protected $fillable=['name','mobile','email','headline','profile_url','job_title','publicProfileUrl','summary','user_id'];

    protected $hidden=['remember_token'];
}
