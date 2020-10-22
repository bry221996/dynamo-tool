<?php

use App\ApiLog;
use App\Jobs\GenerateLogs;
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
    dispatch(new GenerateLogs());

    dd('done');
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
