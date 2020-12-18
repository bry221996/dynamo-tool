<?php

namespace App\Console\Commands;

use App\ApiLog;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\WakandaLog;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Storage;

class GenerateApiLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:api-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $date = '2020-12-17';

        Storage::disk('public')->makeDirectory($date);

        $headers = ['MSISDN', 'Query_Date', 'Message', 'Error_Response', 'HTTP_Method', 'SKU', 'Status_Code', 'Activity_Date'];

        $mainWriter = WriterEntityFactory::createCSVWriter();

        $mainWriter->openToFile(public_path("$date/api-logs-report-$date.csv"));

        $mainWriter->addRow(WriterEntityFactory::createRowFromArray($headers));

        ApiLog::where('query_date', $date)
            ->where('mobile', 'contains', '09178512255')
            ->chunk(5000, function ($records) use ($mainWriter) {
                dump(count($records));
                foreach ($records as $apiLog) {
                    $message = isset($apiLog->message) ? $apiLog->message : '';

                    $values = [
                        $apiLog->mobile,
                        $apiLog->query_date,
                        $message,
                        substr(json_encode(collect($apiLog->error_response)), 0, 10000),
                        $apiLog->http_method,
                        json_encode(collect($apiLog->sku)),
                        $apiLog->status_code,
                        $apiLog->created_at,
                    ];

                    $rowFromValues = WriterEntityFactory::createRowFromArray($values);
                    $mainWriter->addRow($rowFromValues);
                }
            });

        $mainWriter->close();
    }
}
