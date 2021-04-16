<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Storage;
use App\Libraries\Globe;

use App\Customer;
class GenerateTotalRewardUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:total-reward-users';
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
        $date = '2021-03-08';
        // $mobiles = [];

        // foreach($mobiles as $mobile){
            // Storage::disk('public')->makeDirectory('total-reward-users/' . $date);
            Storage::disk('public')->makeDirectory($date);

            $headers = ['MSISDN', 'Account_Number', 'Subs_ID', 'Registration_Date', 'App_Version', 'Brand_Type', 'Device', 'Device_ID'];
            $mainWriter = WriterEntityFactory::createCSVWriter();

            // $mainWriter->openToFile(public_path("total-reward-users/$date/total-reward-users-report-$date-$mobile.csv"));
            $mainWriter->openToFile(public_path("$date/total-reward-users-report-$date.csv"));

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
                    Customer::select(['mobile', 'account_number', 'brandtype', 'app_version', 'created_at',])
                        // ->where('mobile', $mobile)
                        ->whereNotNull('first_name')
                        ->whereNotNull('last_name')
                        ->whereNotNull('email')
                        ->with(['mobiles'])
                        ->where('created_at', 'between', [$from, $to])
                        ->chunk(5000, function ($records) use ($mainWriter, &$total, &$overallCount, $from) {
                            $total = $total + count($records);
                            $overallCount = $overallCount + count($records);
                            dump($from . 'on going: ' . $total . ' Overall Count: ' . $overallCount);
                            foreach ($records as $transactionHistory) {
                                if (isset($transactionHistory->mobiles) && count($transactionHistory->mobiles) > 0) {

                                    foreach ($transactionHistory->mobiles as $mobile) {
                                        $values = [
                                            $transactionHistory->mobile,
                                            $transactionHistory->account_number,
                                            null,
                                            $transactionHistory->created_at,
                                            $transactionHistory->app_version,
                                            strtoupper(Globe::brandType($transactionHistory->brandtype)),
                                            $mobile->device_type,
                                            $mobile->device
                                        ];
                                    }
                                } else {
                                    $values = [
                                        $transactionHistory->mobile,
                                        $transactionHistory->account_number,
                                        null,
                                        $transactionHistory->created_at,
                                        $transactionHistory->app_version,
                                        Globe::brandType($transactionHistory->brandtype),
                                        null,
                                        null
                                    ];
                                }
                                $rowFromValues = WriterEntityFactory::createRowFromArray($values);
                                $mainWriter->addRow($rowFromValues);
                                // $subWriter->addRow($rowFromValues);
                            }
                        });
                    // $subWriter->close();
                });
                $mainWriter->close();
        // }
    }
}