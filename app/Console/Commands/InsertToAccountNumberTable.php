<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

use App\AccountNumber;
use App\Libraries\AccountNumbers;
use App\Libraries\Mobiles;
class InsertToAccountNumberTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert:account-numbers';
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
        $mobiles = Mobiles::mobiles();
        $account_numbers = AccountNumbers::account_numbers();

        for($i = 0; $i < count($mobiles); $i++){
            $mobile = strlen($mobiles[$i]) == 11 ? $mobiles[$i] : substr($mobiles[$i], 2);
            $account_number = $account_numbers[$i];

            dump('Now Creating: ' . $mobile . ' with ' . $account_number);

            AccountNumber::create([
                'mobile' => $mobile,
                'account_number' => $account_number
            ]);
        }
    }
}