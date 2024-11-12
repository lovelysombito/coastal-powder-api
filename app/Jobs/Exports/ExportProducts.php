<?php

namespace App\Jobs\Exports;

use App\Exports\ProductExport;
use App\Notifications\ProductExport as ProductExportNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class ExportProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $fileName = Uuid::uuid4()->toString().'-products.csv';
        $export = Excel::store(new ProductExport(), $fileName, 'local', \Maatwebsite\Excel\Excel::CSV);

        $url = Storage::disk('local')->path($fileName);

        $this->user->notify(new ProductExportNotification($url));

        Storage::disk('local')->delete($fileName);
    }
}
