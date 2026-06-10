<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoteUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $redCount;
    public int $blueCount;

    /**
     * Create a new event instance.
     */
    public function __construct(int $redCount, int $blueCount)
    {
        $this->redCount = $redCount;
        $this->blueCount = $blueCount;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('presence-live-votes')
        ];
    }

    /**
     * Get the event name to broadcast as.
     * 
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'voted';
    }
}
