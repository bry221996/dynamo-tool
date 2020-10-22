<?php

namespace App\Jobs;

use App\ApiLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

class GenerateLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $writer = WriterEntityFactory::createCSVWriter();
        $fileName = date('m-d-Y_h_i_a');
        $writer->openToFile(public_path("api-$fileName.csv"));
        $headers = WriterEntityFactory::createRowFromArray(['MSISDN', 'Query_Date', 'Message', 'Error_Response', 'HTTP_Method', 'SKU', 'Status_Code', 'Activity_Date']);
        $writer->addRow($headers);

        ApiLog::where('query_date', '2020-06-19')
            ->whereIn('mobile', ['09175342268'])
            ->chunk(1000, function ($records) use ($writer) {
                foreach ($records as $log) {
                    $message = isset($log->message) ? $log->message : '';
                    $values = [
                        $log->mobile,
                        $log->query_date,
                        $message,
                        json_encode(collect($log->error_response)),
                        $log->http_method,
                        json_encode(collect($log->sku)),
                        $log->status_code,
                        $log->created_at,
                    ];
                    $rowFromValues = WriterEntityFactory::createRowFromArray($values);
                    $writer->addRow($rowFromValues);
                }
            });

        $writer->close();
    }
}
