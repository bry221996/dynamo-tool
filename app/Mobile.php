<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Mobile extends Model
{
    protected $connection = "mysql_api";
    
	// SCOPE //
    public function scopeMobiles( $query, $mobiles ){
    	return $query->whereIn( "mobile", $mobiles );
    }
}
