<?php

namespace App;

use Excel;
use Uuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;
    
	protected $connection = "mysql_api";

	public $table = "users";

	protected $fillable = [
		"mobile",
		"brandtype"
	];

	protected $search = [
		"first_name",
		"last_name",
		"mobile",
		"email",
		"province",
		"municipality",
	];

	protected $appends = [
		"age"
	];

	// BOOT //
	protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Uuid::generate()->string;
            $model->token = str_random( 28 );
        });
    }

    // MUTATION //
    public function setBirthdateAttribute( $value ){
    	$this->attributes['birthdate'] = date('Y-m-d', strtotime( $value ));
    }

	// ACCESSOR //
	public function getFullNameAttribute(){
		return "<a href='customers/history/{$this->mobile}'>". $this->first_name . " " . $this->last_name . '</a>';
	}

	public function getNameAttribute(){
		return $this->first_name . " " . $this->last_name;
	}

//	public function getCreatedAtAttribute( $value ){
//		return date( "F j, Y", strtotime( $value ) );
//	}

	public function getBirthdateAttribute( $value ){
		return date( "F j, Y", strtotime( $value ) );
	}

	public function getAgeAttribute(){
		return Carbon::parse( $this->birthdate )->age;
	}

	// SCOPE
	public function scopeMobile( $query, $mobile ){
        return $query->where( "mobile", $mobile );
    }

    public function scopeSearch( $query ){

    	$search = request()->query( "search");
    	$from = request()->query( "from");
    	$to = request()->query( "to");
    	

    	$fields = $this->search;

    	$query->where(function($query) use ($search, $fields){
	    	foreach ($fields as $field) {
	    		$query = $query->orWhere( $field, "LIKE", "%{$search}%" );
	    	}
	    });

	    $name = (strpos($search, ' ') !== false) ? explode(' ', $search) : [];
		if( $name ){
			$query->orWhere( 'first_name', "LIKE", "%{$name[0]}%" );
			$query->orWhere( 'last_name', "LIKE", "%{$name[1]}%" );
		}

	    if( $from && $to ) $query->where('created_at','>=',$from . ' 00:00:00')->where('created_at','<=',$to . ' 23:59:59');

	    $query->orderBy("created_at","ASC");

	    return $query;
    }

    public function scopeTargetsegment( $query, $target_segment ){
    	$birth_month = json_decode( $target_segment->birth_month );
    	$subsriber_type = json_decode( $target_segment->subscriber_type );
    	$age_group = json_decode( $target_segment->age_group );

    	if( $target_segment->gender ){
    		if( strtolower($target_segment->gender) != 'both' ) $query->where( 'gender', $target_segment->gender );
    	}

    	if( $age_group ){
    		$age_cnt = 0;
            foreach ($age_group as $range) {
                $range = explode( '-', $range );
                $max = ( $range[1] == 'above' ) ? 100 : $range[1];
                $minDate = Carbon::today()->subYears($max);
				$maxDate = Carbon::today()->subYears($range[0])->endOfDay();
				if( $age_cnt == 0 )
					$query->whereBetween( 'birthdate', [$minDate, $maxDate] );
				else
					$query->orWhereBetween( 'birthdate', [$minDate, $maxDate] );
				
				$age_cnt++;
            }
    	}

    	if( $birth_month ){
    		$bday_cnt = 0;
    		foreach ($birth_month as $month) {
    			if( $bday_cnt == 0 )
    				$query->whereRaw('MONTH(birthdate)', Carbon::parse($month)->month );
    			else
    				$query->orWhereRaw('MONTH(birthdate)', Carbon::parse($month)->month );
    			// $query->whereMonth( 'birthdate', Carbon::parse($month)->month);
    			$bday_cnt++;
    		}
    	}

    	if( $subsriber_type ){
    		$query->whereIn('brandtype', $subsriber_type);
    	}

    	return $query;
    }


    public function scopeTargetgender( $query, $target_segment ){
    	if( $target_segment->gender ){
    		if( strtolower($target_segment->gender) != 'both' ) $query->where( 'gender', $target_segment->gender );
    	}
    	return $query;
    }

    public function scopeTargetage( $query, $target_segment ){
    	$age_group = json_decode( $target_segment->age_group );

    	if( $age_group ){
    		$age_cnt = 0;
            foreach ($age_group as $range) {
                $range = explode( '-', $range );
                $max = ( $range[1] == 'above' ) ? 100 : $range[1];
                $minDate = Carbon::today()->subYears($max);
				$maxDate = Carbon::today()->subYears($range[0])->endOfDay();
				if( $age_cnt == 0 )
					$query->whereBetween( 'birthdate', [$minDate, $maxDate] );
				else
					$query->orWhereBetween( 'birthdate', [$minDate, $maxDate] );
				
				$age_cnt++;
            }
    	}

    	return $query;
    }

    public function scopeTargetbirthmonth( $query, $target_segment ){
    	$birth_month = json_decode( $target_segment->birth_month );

    	if( $birth_month ){
    		$bday_cnt = 0;
    		foreach ($birth_month as $month) {
    			if( $bday_cnt == 0 )
    				$query->whereRaw('MONTH(birthdate)', Carbon::parse($month)->month );
    			else
    				$query->orWhereRaw('MONTH(birthdate)', Carbon::parse($month)->month );
    			// $query->whereMonth( 'birthdate', Carbon::parse($month)->month);
    			$bday_cnt++;
    		}
    	}

    	return $query;
    }

    public function scopeTargetbrandtype( $query, $target_segment ){
    	$subsriber_type = json_decode( $target_segment->subscriber_type );

    	if( $subsriber_type ){
    		$query->whereIn('brandtype', $subsriber_type);
    	}

    	return $query;
    }

    // RELATIONSHIP //
    public function mobiles(){
    	return $this->hasMany('App\Mobile', 'mobile', 'mobile');
    }
}
