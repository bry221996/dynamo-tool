<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\DailyActiveUser;
use App\Libraries\Globe;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Storage;
class GenerateDailyActiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:daily-active-users';
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
        $dates = ['2021-03-12', '2021-03-13', '2021-03-14', '2021-03-15', '2021-03-16'];
        // $mobiles = [];

        foreach($dates as $date){
            // Storage::disk('public')->makeDirectory('daily-active-users/' . $date);
            // Storage::disk('public')->makeDirectory($date);

            $headers = ['Activity_Date', 'MSISDN', 'Account_Number', 'Subs_ID', 'App_Version', 'Brand_Type', 'Registration_Date', 'Device', 'Device_ID', 'Number_of_Sessions'];
            $mainWriter = WriterEntityFactory::createCSVWriter();

            $mainWriter->openToFile(public_path("daily-active-users/daily-active-users-report-$date.csv"));
            // $mainWriter->openToFile(public_path("$date/daily-active-users-report-$date.csv"));

            $mainWriter->addRow(WriterEntityFactory::createRowFromArray($headers));
            $range = [
                (object) ['start' => '00:00:00+08:00', 'end' => '23:59:59+08:00']
            ];
            $overallCount = 0;
            // dump('Generating: ' . $date . ' for ' . $mobile);
            collect($range)

                // ->each(function ($range) use ($date, $mainWriter, $headers, &$overallCount, $mobile) {
                ->each(function ($range) use ($date, $mainWriter, $headers, &$overallCount) {

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
                            ->map(function ($dau) {
                                $dau['account_number'] = $dau['account_number'] ?? null;
                                return $dau;
                            })->toArray();

                        DB::table('temp_daily_active_users')->insert($chunkDAU);
                    }

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
                DB::table('temp_daily_active_users')->where('query_date', $date)->delete();
                $mainWriter->close();
        }
    }
}