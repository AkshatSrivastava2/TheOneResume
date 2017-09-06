<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Profile;

class Company extends Model
{
    //
    protected $table='companies';

    protected $fillable=['company_name','company_address','user_id','started_on','ended_on','title'];
}
