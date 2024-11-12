<?php

namespace App\Events;

use App\Models\LineItems;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LineItemUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lineitem;

    public function __construct(LineItems $lineitem)
    {
        $this->lineitem = $lineitem;
    }
}
