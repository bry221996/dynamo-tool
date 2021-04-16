<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

use App\DailyActiveUser;
use App\AccountNumber;

use App\Libraries\Globe;
use App\Libraries\Mobiles;
use App\Libraries\AccountNumbers;

use Storage;
class GenerateDAUByAppVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:dau-by-app-version';
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
        ini_set('memory_limit', '2048M');
        $dates = ['2021-03-21','2021-03-22','2021-03-23','2021-03-24','2021-03-25','2021-03-26','2021-03-27','2021-03-28','2021-03-29','2021-03-30','2021-03-31'];
        $app_version = '3.2.27';
        $device = 'ios';
        // $dates = ['2020-01-16'];
        // $mobiles = [];

        // Storage::disk('public')->makeDirectory('daily-active-users/' . $date);
        // Storage::disk('public')->makeDirectory($date);

        $headers = ['Activity_Date', 'MSISDN', 'Account_Number', 'Subs_ID', 'App_Version', 'Brand_Type', 'Registration_Date', 'Device', 'Device_ID', 'Number_of_Sessions'];
        $mainWriter = WriterEntityFactory::createCSVWriter();

        $dateInFilename = 'March_' . $app_version . '-3';
        $mainWriter->openToFile(public_path("daily-active-users/DAU_REPORT_$dateInFilename.csv"));
        // $mainWriter->openToFile(public_path("$date/daily-active-users-report-$date.csv"));

        $mainWriter->addRow(WriterEntityFactory::createRowFromArray($headers));
        $range = [
            (object) ['start' => '00:00:00+08:00', 'end' => '23:59:59+08:00']
        ];
        $overallCount = 0;
        // dump('Generating: ' . $date . ' for ' . $mobile);
        foreach($dates as $date){
            collect($range)

                // ->each(function ($range) use ($date, $mainWriter, $headers, &$overallCount, $mobile) {
                ->each(function ($range) use ($date, $mainWriter, $headers, &$overallCount, $app_version, $device) {

                    dump('Generating: DAU report for date ' . $date);
                    // $subWriter = WriterEntityFactory::createCSVWriter();
                    // $subWriter->openToFile(public_path("$date/$from.csv"));
                    // $subWriter->addRow(WriterEntityFactory::createRowFromArray($headers));
                    $total = 0;

                    $dailyActiveUsers = DailyActiveUser::where('query_date', $date)->get();
                    dump('Daily Active Users data has been successfully pulled from DynamoDB.');

                    dump('Deleting temp_daily_active_users table...');

                    DB::table('temp_daily_active_users')->where('query_date', $date)->delete();
                    dump('temp_daily_active_users table records with query_date ' . $date . ' has been deleted.');

                    foreach (array_chunk($dailyActiveUsers->toArray(), 1000) as $chunkDAU) {
                        $chunkDAU = collect($chunkDAU)
                            ->where('app_version', $app_version)
                            ->where('device', $device)
                            ->map(function ($dau) {
                                if ($dau['brand_type'] == 'gah'){
                                    dump('Checking account number of ' . $dau['mobile']);
                                }
                                $dau['account_number'] = $dau['brand_type'] == 'gah' && AccountNumber::where('mobile', $dau['mobile'])->first() ? AccountNumber::where('mobile', $dau['mobile'])->first()->account_number : null;
                                return $dau;
                            })->toArray();
                        
                        dump('Inserting chunk to temp_daily_active_users table...');
                        DB::table('temp_daily_active_users')->insert($chunkDAU);
                        dump('Chunk inserted.');
                    }

                    dump('Generating report...');

                    DB::table('temp_daily_active_users')
                        ->select(
                            'mobile',
                            'account_number',
                            DB::raw('max(activity_date) as activity_date'),
                            DB::raw('max(device) as device'),
                            DB::raw('max(app_version) as app_version'),
                            DB::raw('max(user_device_id) as user_device_id'),
                            DB::raw('max(user_registration_date) as user_registration_date'),
                            DB::raw('max(brand_type) as brand_type'),
                            DB::raw('count(mobile) as number_of_session')
                        )
                        ->where('query_date', $date)
                        ->groupBy('mobile', 'account_number', 'device', 'user_device_id')
                        ->orderBy('activity_date')
                        ->chunk(5000, function ($records) use ($mainWriter, &$total, &$overallCount, $date) {
                            $total = $total + count($records);
                            $overallCount = $overallCount + count($records);
                            dump($date . 'on going: ' . $total . ' Overall Count: ' . $overallCount);
                            foreach ($records as $dailyActiveUser) {
                                $values = [
                                    $dailyActiveUser->activity_date,
                                    $dailyActiveUser->mobile,
                                    $dailyActiveUser->account_number,
                                    "",
                                    $dailyActiveUser->app_version,
                                    strtoupper(Globe::brandType($dailyActiveUser->brand_type)),
                                    $dailyActiveUser->user_registration_date,
                                    $dailyActiveUser->device,
                                    $dailyActiveUser->user_device_id,
                                    $dailyActiveUser->number_of_session,
                                ];
                                $rowFromValues = WriterEntityFactory::createRowFromArray($values);
                                $mainWriter->addRow($rowFromValues);
                                // $subWriter->addRow($rowFromValues);
                            }
                        });
                    // $subWriter->close();
                });
                dump('Deleting temp_daily_active_users table...');
                DB::table('temp_daily_active_users')->where('query_date', $date)->delete();
                dump('temp_daily_active_users table records with query_date ' . $date . ' has been deleted.');
        }

        $mainWriter->close();
    }
}