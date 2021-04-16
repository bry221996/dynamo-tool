<?php

namespace App;

use BaoPham\DynamoDb\DynamoDbModel;


class DailyActiveUser extends DynamoDbModel
{
    protected $table = "gr_prod_daily_user_activity";
    protected $compositeKey = ['mobile', 'activity_date'];
    public $timestamps = false;

    public function __construct()
    {
        $this->setTable(env('DAU_TABLE')); //Change DAU Table
        $this->setDynamoDbIndexKeys([
            "gr_prod_dau_query_date_idx" => [
                'hash' => 'query_date'
            ]
        ]);

    }
}
