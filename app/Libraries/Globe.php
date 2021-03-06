<?php

namespace App\Libraries;

class Globe{

	public static function parseMobile( $mobile ){

		if( strlen( $mobile ) == 10 ) return "0{$mobile}";
		if( strlen( $mobile ) == 11 ) return $mobile;

		return str_replace( "+63", "0", $mobile );
	}


	public static function subscriber( $mobile ){

		$business = [
			"091757",
		];

		$postpaid = [
			"090505",
			"090598",
			"090599",
			"090698",
			"090699",
			"09171[0,1,2,3,4,5,8,9]",
			"09173[0,1,2]",
			"09175[0-9]",
			"09176[2,3,5,7,8]",
			"09177[0,1,2,7,9]",
			"09178[0-9]",
			"09778[0-9]",
		];

		$prepaid = [
			"092700",
			"092707",
			"092708",
			"090509",
			"0905[2-4][0-9]",
			"09055[0-7]",
			"090566",
			"0906[0,2,3,4,5][0-9]",
			"0915[0-9][0-9]",
			"0916[2-7][0-9]",
			"09172[0,4,5,7]",
			"09173[3-9]",
			"09174[0-9]",
			"09176[0,1,4,6,9]",
			"09177[3,4,5,6,8]",
			"09179[0-9]",
			"09260[0-9]",
			"09266[1-9]",
			"09267[0-5]",
			"09270[1-6]",
			"09271[1-9]",
			"0927[2-9][0-9]",
			"093504",
			"09360[0,5]",
			"09369[0-9]",
			"0945[1-9][0-9]",
			"094501",
			"095501",
			"095601",
			"095606",
			"095607",
			"095608",
			"095609",
			"0956[1-9][0-9]",
			"097507",
			"097511",
			"097608",
			"097611",
			"0977[0,1,2,3,4,6,7][0-9]",
			"0995[0-9][0-9]",
			"099603",
			"099611",
			"099704",
			"099711",
		];

		$tm = [
			"071600",
			"09050[2,3,4,6,7,8]",
			"09051[0-9]",
			"09055[8,9]",
			"09056[0-5,7-9]",
			"0905[7-9][0-9]",
			"0906[1,6,7,8][0-9]",
			"09069[0-7]",
			"09161[0-9]",
			"09168[2-9]",
			"09169[0-9]",
			"0926[1-5][0-9]",
			"092660",
			"09267[6-9]",
			"0926[8,9][0-9]",
			"092710",
			"09350[0-3,5-9]",
			"0935[1-9][0-9]",
			"09360[2-4,6-9]",
			"0936[1-8][0-9]",
			"094100",
			"094502",
			"09550[2,6-9]",
			"0955[1-9][0-9]",
			"095602",
			"09750[0-6,8-9]",
			"09751[0,2-9]",
			"0975[2-9][0-9]",
			"097607",
			"097610",
			"099602",
			"099610",
			"09970[3,6-9]",
			"09971[0,2-9]",
			"0997[2-9][0-9]",
		];

		if( preg_match( "/^(". implode("|", $business) .")\d{5}$/", $mobile ) ) return "business";
		if( preg_match( "/^(". implode("|", $postpaid) .")\d{5}$/", $mobile ) ) return "postpaid";
		if( preg_match( "/^(". implode("|", $prepaid) .")\d{5}$/", $mobile ) ) return "ghp";
		if( preg_match( "/^(". implode("|", $tm) .")\d{5}$/", $mobile ) ) return "tm";

		return "error";
	}

    public static function brandType( $brandType ){
	    $brandType = strtolower(trim($brandType));

	    if($brandType == "ghp") {
	        return "GP";
        } elseif ($brandType == "tm"){
            return "TM";
        } elseif ($brandType == "postpaid"){
            return "POST";
        } else {
	        return $brandType;
        }
    }

}