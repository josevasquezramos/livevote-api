<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use OpenApi\Attributes as OA;

class BroadcastAuthController extends Controller
{
    #[OA\Post(
        path: "/api/broadcasting/auth",
        summary: "Authenticate broadcasting connection",
        description: "Authorizes a guest user to join a presence channel in Reverb/Pusher.",
        tags: ["Broadcasting"],
        parameters: [
            new OA\Parameter(
                name: "X-Guest-Id",
                in: "header",
                required: true,
                description: "Unique identifier for the guest sent from the frontend.",
                schema: new OA\Schema(type: "string", example: "user_987654321")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Data automatically generated and sent by Laravel Echo.",
            content: new OA\MediaType(
                mediaType: "application/x-www-form-urlencoded",
                schema: new OA\Schema(
                    required: ["socket_id", "channel_name"],
                    properties: [
                        new OA\Property(property: "socket_id", type: "string", example: "12345.67890"),
                        new OA\Property(property: "channel_name", type: "string", example: "presence-live-votes")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful authentication (Pusher/Reverb signature)",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "auth", type: "string", example: "app-key:d5c4b123..."),
                        new OA\Property(property: "channel_data", type: "string", example: "{\"user_id\":\"user_987654321\",\"user_info\":{\"id\":\"user_987654321\"}}")
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden / Unauthorized"
            )
        ]
    )]
    public function authenticate(Request $request)
    {
        $guestId = $request->header('X-Guest-Id');

        $user = new User();
        $user->setAttribute('id', $guestId);
        Auth::setUser($user);

        return Broadcast::auth($request);
    }
}