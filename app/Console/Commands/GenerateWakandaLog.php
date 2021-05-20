<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\WakandaLog;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Storage;
class GenerateWakandaLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:wakanda-logs';
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
        $dates = ['2021-05-19'];
        // $mobiles = [];

        foreach($dates as $date){
            // Storage::disk('public')->makeDirectory('wakanda-logs/' . $date);
            Storage::disk('public')->makeDirectory($date);

            $headers = ['MSISDN', 'Account_Number', 'Message', 'Transaction_Type', 'Millipede_Error', 'Error_Response', 'HTTP_Method', 'SKU', 'Status_Code', 'Activity_Date'];
            $mainWriter = WriterEntityFactory::createCSVWriter();

            $dateInFilename = str_replace('-', '', $date);
            // $mainWriter->openToFile(public_path("wakanda-logs/$date/wakanda-logs-report-$date-$mobile.csv"));
            $mainWriter->openToFile(public_path("$date/wakanda-logs-report-$dateInFilename.csv"));

            $mainWriter->addRow(WriterEntityFactory::createRowFromArray($headers));
            $range = [
                (object) ['start' => '00:00:00+08:00', 'end' => '03:59:59+08:00'],
                (object) ['start' => '04:00:00+08:00', 'end' => '07:59:59+08:00'],
                (object) ['start' => '08:00:00+08:00', 'end' => '11:59:59+08:00'],
                (object) ['start' => '12:00:00+08:00', 'end' => '15:59:59+08:00'],
                (object) ['start' => '16:00:00+08:00', 'end' => '19:59:59+08:00'],
                (object) ['start' => '20:00:00+08:00', 'end' => '23:59:59+08:00']
            ];
            $overallCount = 0;
            // dump('Generating: ' . $date . ' for ' . $mobile);
            collect($range)

                // ->each(function ($range) use ($date, $mainWriter, $headers, &$overallCount, $mobile) {
                ->each(function ($range) use ($date, $mainWriter, $headers, &$overallCount) {

                    $from = $date . 'T' . $range->start;
                    $to = $date . 'T' . $range->end;
                    dump('Generating: ' . $from . ' - ' . $to);
                    // $subWriter = WriterEntityFactory::createCSVWriter();
                    // $subWriter->openToFile(public_path("$date/$from.csv"));
                    // $subWriter->addRow(WriterEntityFactory::createRowFromArray($headers));
                    $total = 0;
                    WakandaLog::where('query_date', $date)
                        // ->where('mobile', $mobile)
                        ->where('created_at', 'between', [$from, $to])
                        ->chunk(5000, function ($records) use ($mainWriter, &$total, &$overallCount, $from) {
                            $total = $total + count($records);
                            $overallCount = $overallCount + count($records);
                            dump($from . 'on going: ' . $total . ' Overall Count: ' . $overallCount);
                            foreach ($records as $wakandaLog) {
                                $values = [
                                    $wakandaLog->mobile,
                                    $wakandaLog->account_number,
                                    $wakandaLog->message,
                                    $wakandaLog->transaction_type,
                                    json_encode(collect($wakandaLog->millipede_error)),
                                    json_encode(collect($wakandaLog->error_response)),
                                    $wakandaLog->http_method,
                                    json_encode(collect($wakandaLog->sku)),
                                    $wakandaLog->status_code,
                                    $wakandaLog->created_at,
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