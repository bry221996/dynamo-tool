<?php

use App\ApiLog;
use App\Jobs\GenerateLogs;
use App\WakandaLog;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/logs', function () {

    $column = strlen(request('mobile')) == 11 ? 'mobile' : 'account_number';

    $log =  WakandaLog::where('query_date', request('date'))
        ->where($column, request('mobile'));

    if (request()->has('method')) {
        $log->where('http_method', strtoupper(request('method')));
    }

    if (request()->has('code')) {
        $log->where('status_code', (int) request('code'));
    }

    if (request()->has('transaction')) {
        $log->where('transaction_type', 'contains', request('transaction'));
    }


    $log = $log->get()->map(function ($log) {
        $log->error_response = json_decode(json_encode($log->error_response), FALSE);
        $log->request = json_decode(($log->request));
        return $log;
    })
        ->sortByDesc('created_at')
        ->toArray();


    return response(['data' => $log]);
});

Route::get('test', function () {
    $mobiles = [
        '09171576378',
        '09156255880',
        '09178786186',
        '09178912931',
        '09178878905',
        '09173172672',
        '09178943893',
        '09176319027'
    ];
    ApiLog::where('query_date', '2020-06-03')
        ->whereIn('mobile', $mobiles)
        ->chunk(1000, function ($records) {
            foreach ($records as $record) {
                dump($record->toArray());
            }
        });

    return response(['datata']);
});
