<?php namespace App\Models;
// generated from /resources/views/admin/db/export-laravel-model-gen.blade.php

use Illuminate\Database\Eloquent\Model;

class OPStops extends Model
{
    protected $table         = 'OP_Stops';
    protected $primaryKey     = 'StopID';
    public $timestamps         = true;
    protected $fillable     = 
    [    
        'StopEventSequenceID', 
        'StopStatedReasonDesc', 
        'StopSubjectAskedToLeave', 
        'StopSubjectStatementsDesc', 
        'StopEnterPrivateProperty', 
        'StopEnterPrivatePropertyDesc', 
        'StopPermissionEnter', 
        'StopPermissionEnterGranted', 
        'StopRequestID', 
        'StopRefuseID', 
        'StopRequestOfficerID', 
        'StopCitationNumber', 
        'StopOfficerRefuseID', 
        'StopSubjectFrisk', 
        'StopSubjectHandcuffed', 
        'StopStopSubjectHandcuffInjYN', 
        'StopSubjectHandcuffInjury', 
        'StopGivenCitation', 
        'StopChargesOther', 
        'StopGivenWarning', 
        'StopTimeStart', 
        'StopTimeEnd', 
        'StopDuration', 
        'StopAllegWrongfulStop', 
        'StopAllegWrongfulEntry', 
        'StopAllegRetaliatoryCharges', 
    ];
}