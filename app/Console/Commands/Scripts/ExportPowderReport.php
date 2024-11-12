<?php

namespace App\Console\Commands\Scripts;

use App\Exports\PowderBreakDownExport;
use App\Models\Job;
use App\Models\User;
use App\Notifications\PowderBreakDownNotification;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

class ExportPowderReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:powder-breakdown {datefrom} {dateto}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $dateFrom = $this->argument('datefrom');
        $dateTo = $this->argument('dateto');

        $jobs = Job::whereBetween('powder_date', [$dateFrom, $dateTo])->with('lineItems')->get();

        $data = [];
        foreach ($jobs as $job) {
            $amount = array_reduce($job->lineItems->toArray(), function ($carry, $item) {
                return $carry + ($item['quantity'] * $item['price']);
            }, 0);
            $payload = [];
            $payload['job_prefix'] = $job->job_prefix;
            $payload['job_number'] = $job->job_number;
            $payload['material'] = $job->material;
            $payload['treatment'] = $job->treatment;
            $payload['powder_date'] = $job->powder_date;
            $payload['powder_bay'] = $job->powder_bay;
            $payload['amount'] = $amount;

            $thirdAmount = $amount / 3;

            if ($job->treatment === "PC" || $job->treatment === "P" || $job->treatment === "C") {
                $payload['treatment_amount'] = 0;
                $payload['powder_amount'] = $amount;
            } else if ($job->treatment === "BPC" || $job->treatment === "TPC") {
                $payload['treatment_amount'] = $thirdAmount;
                $payload['powder_amount'] = $thirdAmount * 2;
            } else if ($job->treatment === "BP" || $job->treatment === "TP" || $job->treatment === "BC" || $job->treatment === "TC") {
                $payload['treatment_amount'] = $amount * 0.5;
                $payload['powder_amount'] = $amount * 0.5;
            } else {
                $payload['treatment_amount'] = "OTHER COMBINATION";
                $payload['powder_amount'] = "OTHER COMBINATION";
            }

            $data[] = $payload;
        }

        $fileName = Uuid::uuid4()->toString().'-jobs.csv';
        $export = Excel::store(new PowderBreakDownExport($data), $fileName, 'local', \Maatwebsite\Excel\Excel::CSV);

        $url = Storage::disk('local')->path($fileName);

        User::where('user_id', env('POWDER_EXPORT_USER_ID'))->first()->notify(new PowderBreakDownNotification($url));

        Storage::disk('local')->delete($fileName);

        return 0;
    }
}
