<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use BaoPham\DynamoDb\DynamoDbModel;

class WakandaLog extends DynamoDbModel
{
    protected $table = "gr_dev_wakanda_logs_v2";
    public function __construct()
    {
        $this->setTable(env('WAKANDA_LOGS_TABLE'));
        $this->setDynamoDbIndexKeys([
            env("WAKANDA_LOGS_TABLE_INDEX") => [
                'hash' => 'query_date'
            ],
            env("WAKANDA_LOGS_TABLE_COMPOSITE_INDEX") => [
                'hash' => 'query_date',
                'range' => 'created_at'
            ]
        ]);
    }
}