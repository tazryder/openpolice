<?php namespace App\Models;
// generated from /resources/views/vendor/survloop/admin/db/export-laravel-model-gen.blade.php

use Illuminate\Database\Eloquent\Model;

class OPLinksCivilianVehicles extends Model
{
    protected $table      = 'op_links_civilian_vehicles';
    protected $primaryKey = 'lnk_civ_vehic_id';
    public $timestamps    = true;
    protected $fillable   = 
    [    
		'lnk_civ_vehic_civ_id', 
		'lnk_civ_vehic_vehic_id', 
		'lnk_civ_vehic_role', 
    ];
    
    // END Survloop auto-generated portion of Model
    
}
