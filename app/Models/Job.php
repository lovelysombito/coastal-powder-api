<?php

namespace App\Models;

use App\Events\JobRemoved;
use App\Events\JobSaved;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;
use Ramsey\Uuid\Uuid;
use App\Models\Treatments;

class Job extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='job_id';
    protected $table = 'job_scheduling';

    protected $dispatchesEvents = [
        'saved' => JobSaved::class,
        'deleted' => JobRemoved::class,
        'trashed' => JobRemoved::class,
    ];

    protected $fillable = [
        'deal_id',
        'job_number',
        'job_prefix',
        'hs_ticket_id',
        'job_status',
        'priority',
        'colour',
        'material',
        'treatment_id',
        'chem_bay_required',
        'chem_status',
        'chem_bay_contractor',
        'chem_contractor_return_date',
        'chem_date',
        'end_chem_date',
        'chem_completed',
        'treatment_bay_required',
        'treatment_bay_contractor',
        'treatment_date',
        'end_treatment_date',
        'treatment_contractor_return_date',
        'treatment_completed',
        'treatment_status',
        'burn_bay_required',
        'burn_bay_contractor',
        'burn_contractor_return_date',
        'burn_date',
        'end_burn_date',
        'burn_status',
        'burn_completed',
        'blast_bay_required',
        'blast_bay_contractor',
        'blast_contractor_return_date',
        'blast_date',
        'end_blast_date',
        'blast_status',
        'blast_completed',
        'powder_bay_required',
        'powder_bay',
        'powder_date',
        'end_powder_date',
        'powder_status',
        'powder_completed',
        'packaged',
        'is_error_redo',
        'material',
        'treatment'
    ];

    protected $hidden = [
        'priority',
        'hs_ticket_id',
        'deleted_at',
    ];

    protected $appends = ['amount', 'job_title'];

    public function searchableAs()
    {
        return 'jobs_index';
    }

    protected function makeAllSearchableUsing($query)
    {
        return $query->with(['deal', 'lines']);
    }

    public function toSearchableArray()
    {
        $array = [
            // Job Properties
            'job_number' => $this->job_number,
            'job_prefix' => $this->job_prefix,
            'job_status' => $this->job_status,
            'colour' => $this->colour,
            'is_error_redo' => $this->is_error_redo,
        ];
 
        return $array;
    }

    protected static function booted()
    {
        static::creating(function ($job) {
            $job->job_id = Uuid::uuid4()->toString();
        });
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function lineItems() {
        return $this->hasMany(LineItems::class, 'job_id', 'job_id');
    }

    public function deal() {
        return $this->belongsTo(Deal::class, 'deal_id', 'deal_id');
    }

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

    public function treatment()
    {
        return $this->hasOne(Treatments::class, 'treatment_id', 'treatment_id');
    }

    public function amount() : Attribute {
        return new Attribute(fn () => round(array_reduce($this->lines->toArray(), function($carry, $line) {
            return $carry + ($line['price'] * $line['quantity']);
        }, 0), 2));
    }

    public function jobTitle() : Attribute {

        return new Attribute(fn () => ($this->is_error_redo === "yes" ? "ERROR | REDO - ":"") . $this->job_prefix . ' ' . $this->job_number );
    }


    public function scopeGetJobs($query, $filters) {
        $query = $query->with(['lines', 'deal', 'job_comments', 'location']);

        if ($filters['colour']){
            $query = $query->where('colour', $filters['colour']);
        }
        
        if ($filters['material']){
            $query = $query->where('material', $filters['material']);
        }

        if ($filters['treatment']){
            $query = $query->whereHas('treatment', function($q) use ($filters) {
                $q->where('treatment', $filters['treatment']);
            });
        }

        if ($filters['status']){
            $query = $query->where('job_status', $filters['status']);
        }

        if ($filters['due_date']) {
            $query = $query->whereHas('deal', function($q) use ($filters) {
                $q->where('promised_date', '<=', $filters['due_date']);
            });
        }

        if ($filters['client_name']) {
            $query = $query->whereHas('deal', function($q) use ($filters) {
                $q->where('client_name', 'like', '%'.$filters['client_name'].'%');
            });
        }

        if ($filters['po_number']) {
            $query = $query->whereHas('deal', function($q) use ($filters) {
                $q->where('po_number', 'like', '%'.$filters['po_number'].'%');
            });
        }

        if ($filters['invoice_number']) {
            $query = $query->whereHas('deal', function($q) use ($filters) {
                $q->where('invoice_number', 'like', '%'.$filters['invoice_number'].'%');
            });
        }

        if ($filters['scheduled_start_date'] && $filters['scheduled_end_date']) {
            $query = $query->where(function($q) use ($filters) {
                $q->whereBetween('chem_date', [$filters['scheduled_start_date'], $filters['scheduled_end_date']])
                    ->orWhereBetween('treatment_date', [$filters['scheduled_start_date'], $filters['scheduled_end_date']])
                    ->orWhereBetween('burn_date', [$filters['scheduled_start_date'], $filters['scheduled_end_date']])
                    ->orWhereBetween('blast_date', [$filters['scheduled_start_date'], $filters['scheduled_end_date']])
                    ->orWhereBetween('powder_date', [$filters['scheduled_start_date'], $filters['scheduled_end_date']])
                    ->orWhere(function ($q2) {
                        $q2->whereNull('chem_date')
                            ->where('chem_bay_required', 'yes');
                        })
                    ->orWhere(function ($q2) {
                        $q2->whereNull('treatment_date')
                            ->where('treatment_bay_required', 'yes');
                        })
                    ->orWhere(function ($q2) {
                        $q2->whereNull('burn_date')
                            ->where('burn_bay_required', 'yes');
                        })
                    ->orWhere(function ($q2) {
                        $q2->whereNull('blast_date')
                            ->where('blast_bay_required', 'yes');
                        })
                    ->orWhere(function ($q2) {
                        $q2->whereNull('powder_date')
                            ->where('powder_bay_required', 'yes');
                        });
            });
        }

        return $query;

    }

    public function scopeGetJobsBayReport($query, $filters) {
        $query = $query->with(['lines', 'deal', 'job_comments', 'location']);
        if ($filters['status']){
            $query = $query->where('job_status', $filters['status']);
        }

        return $query;
    }

    public function scopeGetJobsBayWithDatesReport($query, $filters) {
        $query = $query->with(['lines', 'deal', 'job_comments', 'location']);

        if ($filters['start_date'] && $filters['end_date']) {
            $query = $query->where(function($q) use ($filters) {
                $q->whereBetween('chem_date', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('treatment_date', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('burn_date', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('blast_date', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('powder_date', [$filters['start_date'], $filters['end_date']]);
            });
        }

        return $query;
    }

    public function scopeGetArchiveJobs($query)
    {
        return $query->with(['job_comments.users', 'lines.line_comments', 'lines.line_product', 'deals'])
            ->where('job_status','Complete');
    }

        public function scopeGetOutStandingJobs($query, $startDate,$endDate)
    {
        return $query->with(['job_comments.users', 'lines.line_comments', 'lines.line_product', 'deals'])
            ->whereJobTableOverviewStatus()
            ->whereJobStatus($startDate,$endDate)
            ->orderByJobStatus();
    }

    public function scopeGetKanbanJobs($query, $startDate,$endDate)
    {
        return $query->with(['job_comments.users', 'lines.line_comments', 'lines.line_product', 'deals'])
            ->whereJobStatus($startDate,$endDate)
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

    public function scopeGetJobByBay($query, $bay,$startDate,$endDate)
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
            ->whereBayStatus($bay,$startDate,$endDate);
    }

    public function scopeGetJobByBayKanbanDate($query, $bay, $col,$user,$startDate,$endDate)
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
            ->whereKanbanBayStatus($bay, $col,$startDate,$endDate)
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

    public function scopeWhereBayStatus($query, $bay,$startDate,$endDate)
    {
        $whereCond = ['ready', 'in progress', 'error | redo', 'waiting'];
        $completeStatus = 'complete';

        if ($bay == 'chem') {
            return $query->whereIn('chem_status', $whereCond)->where(function ($query) use ($startDate,$endDate) {
                    $query->WhereBetween('chem_date',  [$startDate,$endDate]);
                });
        } else if ($bay == 'treatment') {
            return $query->whereIn('treatment_status', $whereCond)->where(function ($query) use ($startDate,$endDate) {
                    $query->WhereBetween('treatment_date',  [$startDate,$endDate]);
                });
        } else if ($bay == 'burn') {
            return $query->whereIn('burn_status', $whereCond)->where(function ($query) use ($startDate,$endDate) {
                    $query->WhereBetween('burn_date',  [$startDate,$endDate]);
                });
        } else if ($bay == 'blast') {
            return $query->whereIn('blast_status', $whereCond)->where(function ($query) use ($startDate,$endDate) {
                    $query->WhereBetween('blast_date',  [$startDate,$endDate]);
                });
        } else if ($bay == 'powder-big-batch') {
            return $query->whereIn('powder_status', $whereCond)->where('powder_bay', '=', 'big batch')->where(function ($query) use ($startDate,$endDate) {
                    $query->WhereBetween('powder_date',  [$startDate,$endDate]);
                });
        } else if ($bay == 'powder-small-batch') {
            return $query->whereIn('powder_status', $whereCond)->where('powder_bay', '=', 'small batch')->where(function ($query) use ($startDate,$endDate) {
                    $query->WhereBetween('powder_date',  [$startDate,$endDate]);
                });
        } else if ($bay == 'powder-main-line') {
            return $query->whereIn('powder_status', $whereCond)->where('powder_bay', '=', 'main line')->where(function ($query) use ($startDate,$endDate) {
                    $query->WhereBetween('powder_date',  [$startDate,$endDate]);
                });
        }
    }

    public function scopeWhereKanbanBayStatus($query, $bay, $col,$startDate,$endDate)
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
                return $query->whereIn('chem_status', $whereCond)->WhereBetween('chem_date',  [$startDate,$endDate])->whereRaw('? between chem_date and end_chem_date', [$col])->where('chem_bay_required','yes');
            } else if ($bay == 'treatment') {
                return $query->whereIn('treatment_status', $whereCond)->whereBetween('treatment_date',  [$startDate,$endDate])->whereRaw('? between treatment_date and end_treatment_date', [$col])->where('treatment_bay_required','yes');
            } else if ($bay == 'burn') {
                        return $query->whereIn('burn_status', $whereCond)->whereBetween('burn_date',  [$startDate,$endDate])->whereRaw('? between burn_date and end_burn_date', [$col])->where('burn_bay_required','yes');
            } else if ($bay == 'blast') {
                        return $query->whereIn('blast_status', $whereCond)->whereBetween('blast_date',  [$startDate,$endDate])->whereRaw('? between blast_date and end_blast_date', [$col])->where('blast_bay_required','yes');
            } else if ($bay == 'powder-big-batch') {
                        return $query->whereIn('powder_status', $whereCond)->whereBetween('powder_date',  [$startDate,$endDate])->whereRaw('? between powder_date and end_powder_date', [$col])->where('powder_bay', '=', 'big batch')->where('powder_bay_required','yes');
            } else if ($bay == 'powder-small-batch') {
                        return $query->whereIn('powder_status', $whereCond)->whereBetween('powder_date',  [$startDate,$endDate])->whereRaw('? between powder_date and end_powder_date', [$col])->where('powder_bay', '=', 'small batch')->where('powder_bay_required','yes');
            } else if ($bay == 'powder-main-line') {
                        return $query->whereIn('powder_status', $whereCond)->whereBetween('powder_date',  [$startDate,$endDate])->whereRaw('? between powder_date and end_powder_date', [$col])->where('powder_bay', '=', 'main line')->where('powder_bay_required','yes');
            }
        }
    }

    public function scopeWhereJobStatus($query, $startDate = null, $endDate = null)
    {
        if ($startDate && $endDate) {
            return $query->whereIn('job_status', ['Ready', 'In Progress', 'Error | Redo', 'Awaiting QC', 'QC Passed'])
                ->Where(function ($query) use ($startDate, $endDate) {
                    $query->where('job_status', 'Complete')
                    ->WhereBetween('chem_date',  [$startDate,$endDate])
                    ->orWhereBetween('treatment_date',  [$startDate,$endDate])
                    ->orWhereBetween('burn_date',  [$startDate,$endDate])
                    ->orWhereBetween('blast_date',  [$startDate,$endDate])
                    ->orWhereBetween('powder_date',  [$startDate,$endDate]);
                })
                ->orWhere(function ($query) {
                    $query->whereNull('chem_date')
                    ->orWhereNull('treatment_date')
                    ->orWhereNull('burn_date')
                    ->orWhereNull('blast_date')
                    ->orWhereNull('powder_date');
                });
        } else {
            return $query->whereIn('job_status', ['Ready', 'In Progress', 'Error | Redo', 'Awaiting QC', 'QC Passed']);
        }
    }

    public function scopeWhereKanbanJobStatus($query)
    {
        return $query->whereIn('job_status', ['Ready', 'In Progress', 'Error | Redo', 'Complete']);
    }


    public function scopeGetJobsOverviewDateKanban($query, $col,$user, $startDate = null, $endDate = null)
    {
        if ($col == config('constant.ready_to_schedule')) {
            return $query->with(['job_comments.users', 'lines' => function ($query) {
                $query->WhereNull('chem_date')
                    ->WhereNull('treatment_date')
                    ->WhereNull('burn_date')
                    ->WhereNull('blast_date')
                    ->WhereNull('powder_date');
            }, 'lines.line_comments', 'lines.line_product', 'deals'])
                ->whereJobStatusOverviewDateKanban($col)
                ->orderByJobStatus();
        } else {
            return $query->with(['job_comments.users', 'lines' => function ($query) use ($col) {
                $query->whereDate('chem_date', '=', $col)
                    ->orWhereDate('treatment_date', '=', $col)
                    ->orWhereDate('burn_date', '=', $col)
                    ->orWhereDate('blast_date', '=', $col)
                    ->orWhereDate('powder_date', '=', $col);
            }, 'lines.line_comments', 'lines.line_product', 'deals'])
                ->whereJobStatusOverviewDateKanban($col, $startDate, $endDate)
                ->orderByJobStatus();
        }
    }

    public function scopeWhereJobStatusOverviewDateKanban($query, $col, $startDate = null, $endDate = null)
    {
        if ($col == config('constant.ready_to_schedule')) {
            return $query->whereIn('job_status', ['Ready']);
        } else {
             return $query->whereIn('job_status', ['In Progress', 'Error | Redo'])
                
                ->whereRaw('? between chem_date and end_chem_date', [$col])
                ->orwhereRaw('? between treatment_date and end_treatment_date', [$col])
                ->orwhereRaw('? between burn_date and end_burn_date', [$col])
                ->orwhereRaw('? between blast_date and end_blast_date', [$col])
                ->orwhereRaw('? between powder_date and end_powder_date', [$col]);
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
        $buildQuery = $query->with(['job_comments', 'lines', 'lines.line_comments'])->orderByRaw(DB::raw("FIELD(job_status , 'Awaiting QC','Awaiting QC Passed','QC Passed') ASC"));
        return $buildQuery->whereQCJob();
    }

    public function ScopeWhereQCJob($query)
    {
        $whereCond = ['Awaiting QC', 'Awaiting QC Passed', 'QC Passed'];
        return $query->whereIn('job_status', $whereCond)
            ->orderBy('chem_completed', 'desc')
            ->orderBy('treatment_completed', 'desc')
            ->orderBy('burn_completed', 'desc')
            ->orderBy('blast_completed', 'desc')
            ->orderBy('powder_completed', 'desc');
    }
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
