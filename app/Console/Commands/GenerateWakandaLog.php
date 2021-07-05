<?php

namespace App\Console\Commands;

use App\Imports\ExcelReader;
use App\Jobs\GenerateLogs;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\WakandaLog;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Illuminate\Support\Facades\Storage as FacadesStorage;
use Storage;
use Maatwebsite\Excel\Excel;

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
    public function handles()
    {
        $dates = ['2021-06-27'];
        // $mobiles = [];

        foreach ($dates as $date) {
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
                (object) ['start' => '20:00:00+08:00', 'end' => '23:59:59+08:00'],
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

    public function handle()
    {
        $records = (new ExcelReader)->toCollection('filtered_app_users.xlsx');

        $records = $records->first()->values()->map(function ($record) {
            $record = $record->toArray();
            return [
                'mobile' => "0" . substr((string) $record[0], -10),
                'date' => $record[6],
                'brand' => $record[7]
            ];
        })->sortBy('date')
            ->filter(function ($record) {
                return $record['date'] > '2021-06-29' || $record['date'] < '2021-06-10';
            })
            ->groupBy('date')
            ->each(function ($record, $date) {
                $mobiles = $record->map(function ($record) {
                    return $record['mobile'];
                })->toArray();

                dispatch(new GenerateLogs($date, $mobiles));

                // $headers = ['MSISDN', 'Account_Number', 'Message', 'Transaction_Type', 'Millipede_Error', 'Error_Response', 'HTTP_Method', 'SKU', 'Status_Code', 'Activity_Date'];
                // $writer = WriterEntityFactory::createCSVWriter();
                // $writer->openToFile(storage_path("$date.csv"));
                // $writer->addRow(WriterEntityFactory::createRowFromArray($headers));

                // $mobiles = $record->map(function ($record) {
                //     return $record['mobile'];
                // })->toArray();

                // WakandaLog::where('query_date', $date)
                //     ->chunk(5000, function ($records) use ($writer, $mobiles) {
                //         foreach ($records as $wakandaLog) {
                //             if (in_array($wakandaLog->mobile, $mobiles)) {
                //                 $values = [
                //                     $wakandaLog->mobile,
                //                     $wakandaLog->account_number,
                //                     $wakandaLog->message,
                //                     $wakandaLog->transaction_type,
                //                     json_encode(collect($wakandaLog->millipede_error)),
                //                     json_encode(collect($wakandaLog->error_response)),
                //                     $wakandaLog->http_method,
                //                     json_encode(collect($wakandaLog->sku)),
                //                     $wakandaLog->status_code,
                //                     $wakandaLog->created_at,
                //                 ];
                //                 $rowFromValues = WriterEntityFactory::createRowFromArray($values);
                //                 $writer->addRow($rowFromValues);
                //             }
                //         }
                //     });

                // $writer->close();
            });
    }

    public function handlev3()
    {
        $records = (new ExcelReader)->toCollection('filtered_app_users.xlsx');

        $records = $records->first()->values()->map(function ($record) {
            $record = $record->toArray();
            return [
                'mobile' => "0" . substr((string) $record[0], -10),
                'date' => $record[6],
                'brand' => $record[7]
            ];
        })->sortBy('mobile')
            ->each(function ($record) {
                $mobile = $record['mobile'];
                $date = $record['date'];
                dump("Checking logs of $mobile - $date");

                $logs = WakandaLog::where('query_date', $date)
                    ->where('mobile', $mobile)
                    ->get();

                dump($logs->count());
                if ($logs->count()) {
                    $headers = ['MSISDN', 'Account_Number', 'Message', 'Transaction_Type', 'Millipede_Error', 'Error_Response', 'HTTP_Method', 'SKU', 'Status_Code', 'Activity_Date'];
                    $writer = WriterEntityFactory::createCSVWriter();
                    $writer->openToFile(public_path("$date-$mobile.csv"));
                    $writer->addRow(WriterEntityFactory::createRowFromArray($headers));

                    foreach ($logs as $wakandaLog) {
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
                        $writer->addRow($rowFromValues);
                    }

                    $writer->close();
                }
            });
    }
}
