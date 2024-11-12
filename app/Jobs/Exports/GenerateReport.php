<?php

namespace App\Jobs\Exports;

use App\Exports\PowderBreakDownExport;
use App\Models\User;
use App\Notifications\PowderBreakDownNotification;
use App\Notifications\TreatmentBreakdown;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $jobs, $user;

    public $tries = 0;
    public $maxExceptions = 3;
    public $timeout = 900;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($jobs, $user)
    {
        $this->jobs = $jobs;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = [];
        foreach ($this->jobs as $job) {
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

        $fileName = Uuid::uuid4()->toString().'-report.csv';
        $export = Excel::store(new PowderBreakDownExport($data), $fileName, 'local', \Maatwebsite\Excel\Excel::CSV);

        $url = Storage::disk('local')->path($fileName);

        User::where('user_id', $this->user->user_id)->first()->notify(new TreatmentBreakdown($url));

        Storage::disk('local')->delete($fileName);

        return 0;
    }
}
