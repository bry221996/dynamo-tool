<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountNumber extends Model
{
    protected $connection = "mysql_dau";

	protected $table = "account_numbers";

	protected $fillable = [
		"mobile",
		"account_number",
		"created_at",
		"updated_at"
	];
}
