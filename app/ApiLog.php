<?php

namespace App;

use BaoPham\DynamoDb\DynamoDbModel;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends DynamoDbModel
{
    // protected $table = env('API_LOGS_TABLE');
    protected $primaryKey = 'uuid';

    public function __construct()
    {
        $this->setTable(env('API_LOGS_TABLE'));

        $this->setDynamoDbIndexKeys([
            env("API_LOGS_TABLE_INDEX") => [
                'hash' => 'query_date'
            ]
        ]);
    }
}
