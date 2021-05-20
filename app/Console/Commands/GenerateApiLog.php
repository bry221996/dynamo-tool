<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\ApiLog;
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
        $date = '2021-03-24';
        $mobiles = ['09272509367'];

        foreach($mobiles as $mobile){
            Storage::disk('public')->makeDirectory('api-logs/' . $date);
            $headers = ['MSISDN', 'Message', 'Error_Response', 'HTTP_Method', 'SKU', 'Status_Code', 'Activity_Date'];
            $mainWriter = WriterEntityFactory::createCSVWriter();
            $mainWriter->openToFile(public_path("api-logs/$date/api-logs-report-$date-$mobile.csv"));
            $mainWriter->addRow(WriterEntityFactory::createRowFromArray($headers));
            $range = [
                (object) ['start' => '00:00:00+08:00', 'end' => '03:59:59+08:00'],
                (object) ['start' => '04:00:00+08:00', 'end' => '07:59:59+08:00'],
                (object) ['start' => '08:00:00+08:00', 'end' => '11:59:59+08:00'],
                (object) ['start' => '12:00:00+08:00', 'end' => '15:59:59+08:00'],
                (object) ['start' => '16:00:00+08:00', 'end' => '19:59:59+08:00'],
                (object) ['start' => '20:00:00+08:00', 'end' => '23:59:59+08:00'],
            ];
            $overallCount = 0;
            dump('Generating: ' . $date . ' for ' . $mobile);
            collect($range)
                ->each(function ($range) use ($date, $mainWriter, $headers, &$overallCount, $mobile) {
                    // $from = $date . 'T' . $range->start;
                    // $to = $date . 'T' . $range->end;
                    // $subWriter = WriterEntityFactory::createCSVWriter();
                    // $subWriter->openToFile(public_path("$date/$from.csv"));
                    // $subWriter->addRow(WriterEntityFactory::createRowFromArray($headers));
                    $total = 0;
                    $query = ['query_date' => $date, 'mobile' => $mobile];
                    ApiLog::where($query)
                        // ->where('created_at', 'between', [$from, $to])
                        ->chunk(5000, function ($records) use ($mainWriter, &$total, &$overallCount, $date) {
                            $total = $total + count($records);
                            $overallCount = $overallCount + count($records);
                            dump($date . 'on going: ' . $total . ' Overall Count: ' . $overallCount);
                            foreach ($records as $apiLog) {
                                $values = [
                                    $apiLog->mobile,
                                    $apiLog->message,
                                    json_encode(collect($apiLog->error_response)),
                                    $apiLog->http_method,
                                    json_encode(collect($apiLog->sku)),
                                    $apiLog->status_code,
                                    $apiLog->created_at,
                                ];
                                $rowFromValues = WriterEntityFactory::createRowFromArray($values);
                                $mainWriter->addRow($rowFromValues);
                                // $subWriter->addRow($rowFromValues);
                            }
                        });
                    // $subWriter->close();
                });
                $mainWriter->close();
        }
    }
}