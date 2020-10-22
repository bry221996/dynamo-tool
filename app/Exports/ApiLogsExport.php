<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use App\ApiLog;
use Illuminate\Support\Facades\Log;

class ApiLogsExport implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $collections = collect([]);
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

        ApiLog::where('query_date', '2020-06-10')
            ->whereIn('mobile', $mobiles)
            ->chunk(1000, function ($records) use (&$collections) {
                foreach ($records as $record) {
                    $collections->push($record);
                    Log::error($record->toArray());
                }
                Log::error($collections->count());
            });

        return $collections;
    }
}
