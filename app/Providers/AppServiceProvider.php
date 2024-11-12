<?php

namespace App\Providers;

use App\Models\Colours;
use App\Models\Comment;
use App\Models\CommentRead;
use App\Models\Integration;
use App\Models\Invite;
use App\Models\Products;
use App\Models\QualityControl;
use App\Models\Holiday;
use App\Models\Location;
use App\Models\SearchFilter;
use App\Models\PackingSlip;
use App\Models\User;
use App\Models\UserCode;
use App\Models\NcrFailedOption;
use App\Models\Treatments;
use App\Models\ObjectNotification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Ramsey\Uuid\Uuid;
use App\Models\Material;
use App\Models\MaterialTreatment;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        Fortify::ignoreRoutes();

        User::creating(function (User $user) {
            $user->user_id = Uuid::uuid4()->toString();
        });

        Invite::creating(function (Invite $user) {
            $user->id = Uuid::uuid4()->toString();
        });

        UserCode::creating(function (UserCode $user) {
            $user->user_id = Uuid::uuid4()->toString();
        });

        Colours::creating(function (Colours $colour) {
            $colour->colour_id = Uuid::uuid4()->toString();
        });

        Products::creating(function (Products $product) {
            $product->product_id = Uuid::uuid4()->toString();
        });

        Holiday::creating(function (Holiday $holiday) {
            $holiday->holiday_id = Uuid::uuid4()->toString();
        });

        Treatments::creating(function (Treatments $treatment) {
            $treatment->treatment_id = Uuid::uuid4()->toString();
        });

        Material::creating(function (Material $material) {
            $material->material_id = Uuid::uuid4()->toString();
        });

        MaterialTreatment::creating(function (MaterialTreatment $m) {
            $m->material_treatment_id = Uuid::uuid4()->toString();
        });
        NcrFailedOption::creating(function (NcrFailedOption $option) {
            $option->ncr_failed_id = Uuid::uuid4()->toString();
        });

        Comment::creating(function (Comment $comment) {
            $comment->comment_id = Uuid::uuid4()->toString();
        });

        CommentRead::creating(function (CommentRead $commentRead) {
            $commentRead->comment_read_id = Uuid::uuid4()->toString();
        });

        QualityControl::creating(function (QualityControl $qc) {
            $qc->qc_id = Uuid::uuid4()->toString();
        });

        PackingSlip::creating(function (PackingSlip $ps) {
            $ps->packing_slip_id = Uuid::uuid4()->toString();
        });

        URL::forceScheme('https');
        
        HeadingRowFormatter::extend('lowercase', function($value, $key) {
            return Str::slug(strtolower($value), '_'); 
        });

        SearchFilter::creating(function (SearchFilter $searchFilter) {
            $searchFilter->filter_id = Uuid::uuid4()->toString();
        });

        Location::creating(function (Location $location) {
            $location->location_id = Uuid::uuid4()->toString();
        });

        ObjectNotification::creating(function (ObjectNotification $object_notification) {
            $object_notification->notification_id = Uuid::uuid4()->toString();
        });
        
    }
}
