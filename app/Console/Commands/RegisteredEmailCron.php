<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Mail\RegisteredEmailMail;
use Mail;

class RegisteredEmailCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registeredemail:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Registered Email';

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
     * @return int
     */
    public function handle()
    {
        \Log::info("Cron is Sending New Registered Email!");
        $data = DB::table('users');
        $data = $data->select(DB::raw('count(*) as count'))
        ->whereDate('created_at', '>=', Carbon::now()->subDay()->toDateString());

        $countData = $data->get();
        $totalNewRegisters = 0;
        foreach ($countData as $count) {
            \Log::info($count->count);
            $totalNewRegisters = $count->count;
        }
        if($totalNewRegisters > 0){
          $details = [
            'title' => 'Mail from amerghalayini.com',
            'body' => 'There are ' . $totalNewRegisters . 'new registered users'
          ];

          Mail::to('amer_ghalayini@hotmail.com')->send(new RegisteredEmailMail($details));
        }
    }
}
