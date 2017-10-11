<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    //
    protected $table='linkedin_profiles';

    protected $fillable=['currentlyWorkingAt','profileImageUrl','currentlyWorkingAs','profileUrl','summary','user_id'];

    protected $hidden=['remember_token'];
}
