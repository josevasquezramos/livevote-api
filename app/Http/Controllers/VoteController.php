<?php

namespace App\Http\Controllers;

use App\Enums\Team;
use App\Events\VoteUpdated;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class VoteController extends Controller
{
    /**
     * Handle incoming vote requests, enforce cooldowns, update participant records,
     * and broadcast real-time updates.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function vote(Request $request)
    {
        // 1. Validate incoming request data
        $request->validate([
            'guest_id' => 'required|string',
            'team' => ['required', new Enum(Team::class)],
        ]);

        // 2. Enforce a 5-second cooldown between votes
        $participant = Participant::where('guest_id', $request->input('guest_id'))->first();

        if ($participant && $participant->last_voted_at->addSeconds(5)->isFuture()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please wait before changing teams again.'
            ], 429); // HTTP 429: Too Many Requests
        }

        // 3. Create or update the participant's vote
        Participant::updateOrCreate(
            [
                'guest_id' => $request->input('guest_id')
            ],
            [
                'team' => $request->input('team'),
                'last_voted_at' => now()
            ]
        );

        // 4. Calculate the updated global vote totals
        $redCount = Participant::where('team', Team::RED)->count();
        $blueCount = Participant::where('team', Team::BLUE)->count();

        // 5. Broadcast the real-time update through Reverb
        broadcast(new VoteUpdated($redCount, $blueCount));

        // 6. Return the updated totals to the requester
        return response()->json([
            'status' => 'success',
            'red' => $redCount,
            'blue' => $blueCount
        ]);
    }

    /**
     * Endpoint to retrieve the current vote totals for both teams.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function status()
    {
        $redCount = Participant::where('team', Team::RED)->count();
        $blueCount = Participant::where('team', Team::BLUE)->count();

        return response()->json([
            'red' => $redCount,
            'blue' => $blueCount
        ]);
    }
}
