<?php

namespace App\Jobs\Imports;

use App\Events\JobEvent;
use App\Imports\ProductsImport;
use App\Exceptions\Files\NoFileFoundException;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImportProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filename, $user;
    
    public $timeout = 600;

    public function __construct($filename, $user)
    {
        $this->filename = $filename;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("ImportProducts@handle - {$this->filename}", ["user"=> $this->user->user_id]);
        if (!Storage::exists($this->filename)) {
            Log::error("ImportProducts@handle - File not found {$this->filename}", ["user"=> $this->user->user_id]);
            \Sentry\captureException(new NoFileFoundException("{$this->filename} not found"));
            return;
        }
        try {
            $response = Excel::import(new ProductsImport, $this->filename);

            $this->user->sendProductImportSuccess();
            Log::info("ImportProducts@handle - {$this->filename} completed with 0 errors", ["user"=> $this->user->user_id]);

            Storage::delete($this->filename);
            event(new JobEvent('product'));

            return true;
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            Log::info("ImportProducts@handle - {$this->filename} completed with ".count($failures)." errors", ["user"=> $this->user->user_id]);

            $this->user->sendProductImportFailure($failures);

            Storage::delete($this->filename);
            return;
       }


    }
}
