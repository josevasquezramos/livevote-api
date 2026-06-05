<?php

namespace App\Http\Controllers;

use App\Enums\Team;
use App\Events\VoteUpdated;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use OpenApi\Attributes as OA;

class VoteController extends Controller
{
    #[OA\Post(
        path: '/api/vote',
        summary: 'Submit or change a vote',
        description: 'Handle incoming vote requests, enforce a 5-second cooldown, update participant records, and broadcast real-time updates.',
        tags: ['Voting'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['guest_id', 'team'],
                properties: [
                    new OA\Property(property: 'guest_id', type: 'string', example: 'user_987654321', description: 'Unique identifier for the guest'),
                    new OA\Property(property: 'team', type: 'string', example: 'red', description: 'The team the participant is voting for')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Vote successfully registered',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'red', type: 'integer', example: 152),
                        new OA\Property(property: 'blue', type: 'integer', example: 148)
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation Error (e.g., missing guest_id or invalid team)'
            ),
            new OA\Response(
                response: 429,
                description: 'Too Many Requests (Cooldown active)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Please wait before changing teams again.')
                    ]
                )
            )
        ]
    )]
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

    #[OA\Get(
        path: '/api/status',
        summary: 'Get current vote totals',
        description: 'Endpoint to retrieve the current vote totals for both teams.',
        tags: ['Voting'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful retrieval of vote counts',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'red', type: 'integer', example: 152),
                        new OA\Property(property: 'blue', type: 'integer', example: 148)
                    ]
                )
            )
        ]
    )]
    public function status()
    {
        $redCount = Participant::where('team', Team::RED)->count();
        $blueCount = Participant::where('team', Team::BLUE)->count();

        return response()->json([
            'red' => $redCount,
            'blue' => $blueCount
        ]);
    }

    #[OA\Get(
        path: '/api/participants',
        summary: 'Get list of participants (Paginated)',
        description: 'Retrieves a paginated list of participants.',
        tags: ['Voting'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number for pagination',
                schema: new OA\Schema(type: 'integer', default: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful retrieval of participants',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'guest_id', type: 'string', example: 'device_xyz_123'),
                                    new OA\Property(property: 'team', type: 'string', example: 'red'),
                                    new OA\Property(property: 'last_voted_at', type: 'string', format: 'date-time', example: '2026-06-04T20:00:00.000000Z')
                                ]
                            )
                        ),
                        new OA\Property(property: 'first_page_url', type: 'string', example: 'http://localhost/api/participants?page=1'),
                        new OA\Property(property: 'last_page', type: 'integer', example: 5),
                        new OA\Property(property: 'total', type: 'integer', example: 65)
                    ]
                )
            )
        ]
    )]
    public function participants()
    {
        $participants = Participant::paginate(15);

        return response()->json($participants);
    }
}
