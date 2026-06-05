<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    description: "Here you can find the documentation for the API of LiveVote.",
    title: "API documentation"
)]
abstract class Controller
{
    //
}
