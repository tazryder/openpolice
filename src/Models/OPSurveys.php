<?php namespace App\Models;
// generated from /resources/views/vendor/survloop/admin/db/export-laravel-model-gen.blade.php

use Illuminate\Database\Eloquent\Model;

class OPSurveys extends Model
{
    protected $table      = 'op_surveys';
    protected $primaryKey = 'surv_id';
    public $timestamps    = true;
    protected $fillable   = 
    [    
		'surv_complaint_id', 
		'surv_auth_user_id', 
    ];
    
    // END Survloop auto-generated portion of Model
    
}
