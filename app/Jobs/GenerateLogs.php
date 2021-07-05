<?php

namespace App\Jobs;

use App\Log;
use App\WakandaLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

class GenerateLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;
    protected $mobiles;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($date, $mobiles)
    {
        $this->date = $date;
        $this->mobiles = $mobiles;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $range = [
            (object) ['start' => '00:00:00+08:00', 'end' => '03:59:59+08:00'],
            (object) ['start' => '04:00:00+08:00', 'end' => '07:59:59+08:00'],
            (object) ['start' => '08:00:00+08:00', 'end' => '11:59:59+08:00'],
            (object) ['start' => '12:00:00+08:00', 'end' => '15:59:59+08:00'],
            (object) ['start' => '16:00:00+08:00', 'end' => '19:59:59+08:00'],
            (object) ['start' => '20:00:00+08:00', 'end' => '23:59:59+08:00'],
        ];

        collect($range)
            ->each(function ($range)  {
                $from = $this->date . 'T' . $range->start;
                $to = $this->date . 'T' . $range->end;
                dump("$from -> $to");

                WakandaLog::where('query_date', $this->date)
                    ->whereIn('mobile', $this->mobiles)
                    ->where('created_at', 'between', [$from, $to])
                    ->chunk(5000, function ($records) {
                        $data = $records->map(function ($wakandaLog) {
                           return [
                            'mobile' => $wakandaLog->mobile,
                            'account_number' =>  $wakandaLog->account_number,
                            'message' =>$wakandaLog->message,
                            'transaction_type' =>$wakandaLog->transaction_type,
                            'millipede_error' =>json_encode(collect($wakandaLog->millipede_error)),
                            'response' =>json_encode(collect($wakandaLog->error_response)),
                            'http_method' =>$wakandaLog->http_method,
                            'sku' =>json_encode(collect($wakandaLog->sku)),
                            'status_code' =>$wakandaLog->status_code,
                            'date' =>$wakandaLog->created_at,
                           ];
                        })->toArray();

                        Log::insert($data);
                    });
            });
    }
}
