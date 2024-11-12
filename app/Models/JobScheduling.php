<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobScheduling extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'job_scheduling';
    protected $primaryKey = 'job_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'deal_id',
        'job_number',
        'job_prefix',
        'hs_ticket_id',
        'job_status',
        'priority',
        'colour',
        'material',
        'treatment',
        'chem_bay_required',
        'chem_status',
        'chem_bay_contractor',
        'chem_contractor_return_date',
        'chem_date',
        'chem_completed',
        'treatment_bay_required',
        'treatment_bay_contractor',
        'treatment_date',
        'treatment_contractor_return_date',
        'treatment_completed',
        'treatment_status',
        'burn_bay_required',
        'burn_bay_contractor',
        'burn_contractor_return_date',
        'burn_date',
        'burn_status',
        'burn_completed',
        'blast_bay_required',
        'blast_bay_contractor',
        'blast_contractor_return_date',
        'blast_date',
        'blast_status',
        'blast_completed',
        'powder_bay_required',
        'powder_bay',
        'powder_date',
        'powder_status',
        'powder_completed',
        'packaged',
        'is_error_redo'
    ];

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function amount() : Attribute {
        return new Attribute(fn () => round(array_reduce($this->lines->toArray(), function($carry, $line) {
            return $carry + ($line['price'] * $line['quantity']);
        }, 0), 2));
    }

    protected $appends = ['amount'];

    public function job_comments()
    {
        return $this->hasMany(Comment::class, 'object_id', 'job_id');
    }

    public function deals()
    {
        return $this->belongsTo(Deal::class, 'deal_id', 'deal_id');
    }

    public function lines()
    {
        return $this->hasMany(LineItems::class, 'job_id', 'job_id')
            ->orderBy('chem_date')
            ->orderBy('treatment_date')
            ->orderBy('burn_date')
            ->orderBy('blast_date')
            ->orderBy('powder_date');
    }

    public function quality_control()
    {
        return $this->hasOne(QualityControl::class, 'object_id', 'job_id');
    }

    public function scopeGetOutStandingJobs($query)
    {
        return $query->with(['job_comments.users', 'lines.line_comments', 'lines.line_product', 'deals'])
            ->whereJobTableOverviewStatus()
            ->orderByJobStatus();
    }

    public function scopeGetKanbanJobs($query, $schdeuleDate)
    {
        return $query->with(['job_comments.users', 'lines.line_comments', 'lines.line_product', 'deals'])
            ->whereJobStatus($schdeuleDate)
            ->orderByJobStatus();
    }

    public function scopeOrderByJobStatus($query)
    {
        return $query->orderByRaw(DB::raw("FIELD(job_status , 'Ready', 'In Progress', 'Error | Redo','Awaiting QC', 'QC Passed','Partially Shipped','Complete') ASC"))
            ->orderBy('chem_date')
            ->orderBy('treatment_date')
            ->orderBy('burn_date')
            ->orderBy('blast_date')
            ->orderBy('powder_date');
    }

    public function scopeGetJobByBay($query, $bay)
    {
        $completeStatus = 'complete';
        $whereCond = ['ready', 'in progress', 'error | redo', 'waiting'];
        if ($bay == 'chem') {
            $buildQuery = $query->with(['job_comments', 'lines' => function ($query) use ($whereCond) {
                $query->whereIn('chem_status', $whereCond);
            }, 'lines.line_comments', 'lines.line_product'])->orderBy('chem_date');
        } else if ($bay == 'treatment') {
            $buildQuery = $query->with(['job_comments', 'lines' => function ($query)  use ($whereCond, $completeStatus) {
                $query->whereIn('treatment_status', $whereCond);
            }, 'lines.line_comments', 'lines.line_product'])->orderBy('treatment_date');
        } else if ($bay == 'burn') {
            $buildQuery = $query->with(['job_comments', 'lines' => function ($query)  use ($whereCond, $completeStatus) {
                $query->whereIn('burn_status', $whereCond);
            }, 'lines.line_comments', 'lines.line_product'])->orderBy('burn_date');
        } else if ($bay == 'blast') {
            $buildQuery = $query->with(['job_comments', 'lines' => function ($query)  use ($whereCond, $completeStatus) {
                $query->whereIn('blast_status', $whereCond);
            }, 'lines.line_comments', 'lines.line_product'])->orderBy('blast_date');
        } else if ($bay == 'powder-big-batch') {
            $buildQuery = $query->with(['job_comments', 'lines' => function ($query)  use ($whereCond, $completeStatus) {
                $query->whereIn('powder_status', $whereCond);
            }, 'lines.line_comments', 'lines.line_product'])->orderBy('powder_date');
        } else if ($bay == 'powder-small-batch') {
            $buildQuery = $query->with(['job_comments', 'lines' => function ($query)  use ($whereCond, $completeStatus) {
                $query->whereIn('powder_status', $whereCond);
            }, 'lines.line_comments', 'lines.line_product'])->orderBy('powder_date');
        } else if ($bay == 'powder-main-line') {
            $buildQuery = $query->with(['job_comments', 'lines' => function ($query)  use ($whereCond, $completeStatus) {
                $query->whereIn('powder_status', $whereCond);
            }, 'lines.line_comments', 'lines.line_product'])->orderBy('powder_date');
        }
        return $buildQuery
            ->whereBayStatus($bay);
    }

    public function scopeGetJobByBayKanbanDate($query, $bay, $col)
    {
        $whereCond = ['ready', 'waiting'];
        $from = Carbon::now()->format('Y-m-d');

        $nextDayCount = 1;
        $i = 0;

        do {
            $temp = Carbon::now()->addDays($nextDayCount)->format('Y-m-d');
            if (!in_array(date('l', strtotime($temp)), ["Sunday"]) && !in_array($temp, config('constant.holidayList'))) {
                $i++;
            }

            $nextDayCount++;
        } while ($i < 3);
        $to = Carbon::now()->addDays($nextDayCount - 1)->format('Y-m-d');
        $completeStatus = 'complete';
        $error_status = ['error | redo'];

        if ($col == config('constant.ready_to_schedule')) {
            $whereCond = ['ready', 'error | redo', 'waiting'];

            if ($bay == 'chem') {
                    $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments', 'lines.line_product'])->orderBy('chem_priority', 'asc')->orderBy('chem_date');
            } else if ($bay == 'treatment') {
                    $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments', 'lines.line_product'])->orderBy('treatment_priority', 'asc')->orderBy('treatment_date');
            } else if ($bay == 'burn') {
                    $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments', 'lines.line_product'])->orderBy('burn_priority', 'asc')->orderBy('burn_date');
            } else if ($bay == 'blast') {
                    $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments', 'lines.line_product'])->orderBy('blast_priority', 'asc')->orderBy('blast_date');
            } else if ($bay == 'powder-big-batch') {
                    $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments', 'lines.line_product'])->orderBy('powder_priority', 'asc')->orderBy('powder_date');
            } else if ($bay == 'powder-small-batch') {
                    $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments', 'lines.line_product'])->orderBy('powder_priority', 'asc')->orderBy('powder_date');
            } else if ($bay == 'powder-main-line') {
                    $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments', 'lines.line_product'])->orderBy('powder_priority', 'asc')->orderBy('powder_date');
            }
        } else {
            $whereCond = ['ready', 'in progress', 'waiting'];

            if ($bay == 'chem') {
                $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments', 'lines.line_product'])->orderBy('chem_priority', 'asc')->orderBy('chem_date');
            } else if ($bay == 'treatment') {
                $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments', 'lines.line_product'])->orderBy('treatment_priority', 'asc')->orderBy('treatment_date');
            } else if ($bay == 'burn') {
                $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments', 'lines.line_product'])->orderBy('burn_priority', 'asc')->orderBy('burn_date');
            } else if ($bay == 'blast') {
                $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments', 'lines.line_product'])->orderBy('blast_priority', 'asc')->orderBy('blast_date');
            } else if ($bay == 'powder-big-batch') {
                $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments', 'lines.line_product'])->orderBy('powder_priority', 'asc')->orderBy('powder_date');
            } else if ($bay == 'powder-small-batch') {
                $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments', 'lines.line_product'])->orderBy('powder_priority', 'asc')->orderBy('powder_date');
            } else if ($bay == 'powder-main-line') {
                $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments', 'lines.line_product'])->orderBy('powder_priority', 'asc')->orderBy('powder_date');
            }
        }

        return $buildQuery
            ->whereKanbanBayStatus($bay, $col)
            ->orderByBayStatus($bay);
    }

    public function scopeOrderByBayStatus($query, $bay)
    {
        if ($bay == 'chem') {
            return $query->orderByRaw(DB::raw("FIELD(chem_status , 'ready','waiting','in progress', 'error | redo') ASC"))
                ->orderBy('chem_date');
        } else if ($bay == 'treatment') {
            return $query->orderByRaw(DB::raw("FIELD(treatment_status , 'ready','waiting','in progress', 'error | redo') ASC"))
                ->orderBy('treatment_date');
        } else if ($bay == 'burn') {
            return $query->orderByRaw(DB::raw("FIELD(burn_status , 'ready','waiting','in progress', 'error | redo') ASC"))
                ->orderBy('burn_date');
        } else if ($bay == 'blast') {
            return $query->orderByRaw(DB::raw("FIELD(blast_status , 'ready','waiting','in progress', 'error | redo') ASC"))
                ->orderBy('blast_date');
        } else if ($bay == 'powder') {
            return $query->orderByRaw(DB::raw("FIELD(powder_status , 'ready','waiting','in progress', 'error | redo') ASC"))
                ->orderBy('powder_date');
        }
    }

    public function scopeWhereBayStatus($query, $bay)
    {
        $whereCond = ['ready', 'in progress', 'error | redo', 'waiting'];
        $completeStatus = 'complete';

        if ($bay == 'chem') {
            return $query->whereIn('chem_status', $whereCond);
        } else if ($bay == 'treatment') {
            return $query->whereIn('treatment_status', $whereCond);
        } else if ($bay == 'burn') {
            return $query->whereIn('burn_status', $whereCond);
        } else if ($bay == 'blast') {
            return $query->whereIn('blast_status', $whereCond);
        } else if ($bay == 'powder-big-batch') {
            return $query->whereIn('powder_status', $whereCond)->where('powder_bay', '=', 'big batch');
        } else if ($bay == 'powder-small-batch') {
            return $query->whereIn('powder_status', $whereCond)->where('powder_bay', '=', 'small batch');
        } else if ($bay == 'powder-main-line') {
            return $query->whereIn('powder_status', $whereCond)->where('powder_bay', '=', 'main line');
        }
    }

    public function scopeWhereKanbanBayStatus($query, $bay, $col)
    {
        $from = Carbon::now()->format('Y-m-d');

        $nextDayCount = 1;
        $i = 0;
        do {
            $temp = Carbon::now()->addDays($nextDayCount)->format('Y-m-d');
            if (!in_array(date('l', strtotime($temp)), ["Sunday"]) && !in_array($temp, config('constant.holidayList'))) {
                $i++;
            }

            $nextDayCount++;
        } while ($i < 3);
        $to = Carbon::now()->addDays($nextDayCount - 1)->format('Y-m-d');

        $completeStatus = 'complete';

        if ($col == config('constant.ready_to_schedule')) {
            $whereCond = ['ready', 'error | redo', 'waiting'];
            if ($bay == 'chem') {
                return $query->whereIn('chem_status', $whereCond)->where('chem_bay_required','yes')->whereNull('chem_date');
            } else if ($bay == 'treatment') {
                return $query->where('treatment_bay_required','yes')->whereNull('treatment_date');
            } else if ($bay == 'burn') {
                return $query->whereIn('burn_status', $whereCond)->where('burn_bay_required','yes')->whereNull('burn_date');
            } else if ($bay == 'blast') {
                return $query->whereIn('blast_status', $whereCond)->where('blast_bay_required','yes')->whereNull('blast_date');
            } else if ($bay == 'powder-big-batch') {
                return $query->whereIn('powder_status', $whereCond)->where('powder_bay', '=', 'big batch')->where('powder_bay_required','yes')->whereNull('powder_date');
            } else if ($bay == 'powder-small-batch') {
                return $query->whereIn('powder_status', $whereCond)->where('powder_bay', '=', 'small batch')->where('powder_bay_required','yes')->whereNull('powder_date');
            } else if ($bay == 'powder-main-line') {
                return $query->whereIn('powder_status', $whereCond)->where('powder_bay', '=', 'main line')->where('powder_bay_required','yes')->whereNull('powder_date');
            }
        } else {
            $whereCond = ['ready', 'in progress', 'waiting'];

            if ($bay == 'chem') {
                        return $query->whereIn('chem_status', $whereCond)->whereDate('chem_date', '=', $col)->where('chem_bay_required','yes');
            } else if ($bay == 'treatment') {
                        return $query->whereIn('treatment_status', $whereCond)->whereDate('treatment_date', '=', $col)->where('treatment_bay_required','yes');
            } else if ($bay == 'burn') {
                        return $query->whereIn('burn_status', $whereCond)->whereDate('burn_date', '=', $col)->where('burn_bay_required','yes');
            } else if ($bay == 'blast') {
                        return $query->whereIn('blast_status', $whereCond)->whereDate('blast_date', '=', $col)->where('blast_bay_required','yes');
            } else if ($bay == 'powder-big-batch') {
                        return $query->whereIn('powder_status', $whereCond)->whereDate('powder_date', '=', $col)->where('powder_bay', '=', 'big batch')->where('powder_bay_required','yes');
            } else if ($bay == 'powder-small-batch') {
                        return $query->whereIn('powder_status', $whereCond)->whereDate('powder_date', '=', $col)->where('powder_bay', '=', 'small batch')->where('powder_bay_required','yes');
            } else if ($bay == 'powder-main-line') {
                        return $query->whereIn('powder_status', $whereCond)->whereDate('powder_date', '=', $col)->where('powder_bay', '=', 'main line')->where('powder_bay_required','yes');
            }
        }
    }

    public function scopeWhereJobStatus($query, $schdeuleDate = null)
    {
        if ($schdeuleDate) {
            return $query->whereIn('job_status', ['Ready', 'In Progress', 'Error | Redo', 'Awaiting QC', 'QC Passed'])
                ->whereDate('chem_date', '=', $schdeuleDate)
                ->orWhereDate('treatment_date', '=', $schdeuleDate)
                ->orWhereDate('burn_date', '=', $schdeuleDate)
                ->orWhereDate('blast_date', '=', $schdeuleDate)
                ->orWhereDate('powder_date', '=', $schdeuleDate);
        } else {
            return $query->whereIn('job_status', ['Ready', 'In Progress', 'Error | Redo', 'Awaiting QC', 'QC Passed']);
        }
    }

    public function scopeWhereKanbanJobStatus($query)
    {
        return $query->whereIn('job_status', ['Ready', 'In Progress', 'Error | Redo', 'Complete']);
    }


    public function scopeGetJobsOverviewDateKanban($query, $col)
    {
        if ($col == config('constant.ready_to_schedule')) {
            return $query->with(['job_comments.users', 'lines' => function ($query) {
                $query->WhereNull('chem_date')
                    ->WhereNull('treatment_date')
                    ->WhereNull('burn_date')
                    ->WhereNull('blast_date')
                    ->WhereNull('powder_date')
                    ->whereNull('deleted_at');
            }, 'lines.line_comments', 'lines.line_product', 'deals'])
                ->whereJobStatusOverviewDateKanban($col)
                ->orderByJobStatus();
        } else {
            return $query->with(['job_comments.users', 'lines' => function ($query) use ($col) {
                $query->whereNull('deleted_at')
                    ->whereDate('chem_date', '=', $col)
                    ->orWhereDate('treatment_date', '=', $col)
                    ->orWhereDate('burn_date', '=', $col)
                    ->orWhereDate('blast_date', '=', $col)
                    ->orWhereDate('powder_date', '=', $col);
            }, 'lines.line_comments', 'lines.line_product', 'deals'])
                ->whereJobStatusOverviewDateKanban($col)
                ->orderByJobStatus();
        }
    }

    public function scopeWhereJobStatusOverviewDateKanban($query, $col)
    {
        if ($col == config('constant.ready_to_schedule')) {
            return $query->whereIn('job_status', ['Ready'])->whereNull('deleted_at');;
        } else {
            return $query->whereIn('job_status', ['Ready', 'In Progress', 'Error | Redo'])->whereNull('deleted_at')
                ->whereDate('chem_date', '=', $col)
                ->orWhereDate('treatment_date', '=', $col)
                ->orWhereDate('burn_date', '=', $col)
                ->orWhereDate('blast_date', '=', $col)
                ->orWhereDate('powder_date', '=', $col);
        }
    }

    public function scopeGetJobDispatched($query)
    {
        $buildQuery = $query->with(['job_comments', 'lines' => function ($query) {
        }, 'lines.line_comments', 'lines.line_product'])->orderByRaw(DB::raw("FIELD(job_status ,'Awaiting QC Passed','QC Passed', 'Partially Shipped', 'Complete') ASC"));
        return $buildQuery->whereJobDispatched();
    }

    public function scopeWhereJobDispatched($query)
    {
        $whereCond = ['Awaiting QC Passed', 'QC Passed', 'Partially Shipped', 'Complete'];
        return $query->whereIn('job_status', $whereCond)->orderBy('updated_at');
    }

    public function scopeUpdateJobStatus($query, $id)
    {
        return $query->with(['lines'])
            ->where('job_id', $id);
    }

    public function scopeGetQcJob($query)
    {
        $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments'])->where('job_status', 'Awaiting QC')->orderBy('job_prefix')->orderBy('job_number');
        return $buildQuery;
    }

    // public function ScopeWhereQCJob($query)
    // {
    //     $whereCond = ['Awaiting QC'];
    //     return $query->whereIn('job_status', $whereCond)
    //         ->orderBy('chem_completed', 'desc')
    //         ->orderBy('treatment_completed', 'desc')
    //         ->orderBy('burn_completed', 'desc')
    //         ->orderBy('blast_completed', 'desc')
    //         ->orderBy('powder_completed', 'desc');
    // }
    
    public function scopeWhereJobTableOverviewStatus($query)
    {
        return $query;
        // return $query->where(function($q) {
        //         $q->where('chem_status','waiting')
        //             ->whereNull('chem_date');
        //     })->orWhere(function($q) {
        //         $q->where('burn_status','waiting')
        //             ->whereNull('burn_date');
        //     })->orWhere(function($q) {
        //         $q->where('treatment_status','waiting')
        //             ->whereNull('treatment_date');
        //     })->orWhere(function($q) {
        //         $q->where('blast_status','waiting')
        //             ->whereNull('blast_date');
        //     })->orWhere(function($q) {
        //         $q->where('powder_status','waiting')
        //             ->whereNull('powder_date');
        //     });
    }

    public function scopeGetAllKanbanJobs($query)
    {
        return $query->with(['job_comments.users', 'lines.line_comments', 'lines.line_product', 'deals'])
            ->orderByJobStatus();
    }
}
