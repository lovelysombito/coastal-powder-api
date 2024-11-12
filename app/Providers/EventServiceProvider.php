<?php

namespace App\Providers;

use App\Events\FailedJobSaved;
use App\Events\JobSaved;
use App\Events\JobRemoved;
use App\Events\JobUpdated;
use App\Events\LineItemUpdated;
use App\Listeners\CreateUpdateJobTicket;
use App\Listeners\RemoveJobTicket;
use App\Listeners\UpdateHubSpotJob;
use App\Listeners\UpdateHubSpotLineItem;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        JobSaved::class => [
            CreateUpdateJobTicket::class,
        ],
        JobUpdated::class => [
            UpdateHubSpotJob::class,
        ],
        JobRemoved::class => [
            RemoveJobTicket::class,
        ],
        LineItemUpdated::class => [
            UpdateHubSpotLineItem::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
