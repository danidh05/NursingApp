<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Nursing App API Documentation",
 *     description="API documentation for the Nursing App - A platform connecting users with nursing services.
 *     
 *     🔌 **Real-time Communication**
 *     This API also supports WebSocket channels for real-time updates.
 *     See the 'Real-time Events' tag for event schemas.
 *     
 *     **WebSocket Channels:**
 *     - Chat: private-chat.{threadId}
 *     - Full documentation in routes/channels.php
 *     
 *     **Frontend Integration:**
 *     Subscribe to channels using Laravel Echo:
 *     Echo.private('chat.123').listen('MessageCreated', callback)
 *     ",
 *     @OA\Contact(
 *         email="support@nursingapp.com",
 *         name="API Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and authorization endpoints"
 * )
 * @OA\Tag(
 *     name="Users",
 *     description="User management endpoints"
 * )
 * @OA\Tag(
 *     name="Nurses",
 *     description="Nurse management endpoints"
 * )
 * @OA\Tag(
 *     name="Services",
 *     description="Service management endpoints"
 * )
 * @OA\Tag(
 *     name="Categories",
 *     description="Category management endpoints"
 * )
 * @OA\Tag(
 *     name="Requests",
 *     description="Service request management endpoints"
 * )
 * @OA\Tag(
 *     name="Notifications",
 *     description="Notification management endpoints"
 * )
 * @OA\Tag(
 *     name="About",
 *     description="About page management endpoints"
 * )
 * @OA\Tag(
 *     name="Admin",
 *     description="Admin-only management endpoints"
 * )
 * @OA\Tag(
 *     name="Chat",
 *     description="Temporary request-scoped chat endpoints for real-time communication"
 * )
 * 
 * @OA\Tag(
 *     name="Real-time Events",
 *     description="WebSocket events and broadcasting channels for real-time communication"
 * )
 * 
 * @OA\Response(
 *     response="ChatFeatureDisabled",
 *     description="Chat feature is disabled",
 *     @OA\JsonContent(
 *         @OA\Property(property="message", type="string", example="Chat feature is disabled")
 *     )
 * )
 * 
 * @OA\Response(
 *     response="Unauthorized",
 *     description="User not authenticated",
 *     @OA\JsonContent(
 *         @OA\Property(property="message", type="string", example="Unauthenticated")
 *     )
 * )
 * 
 * @OA\Response(
 *     response="Forbidden",
 *     description="User not authorized",
 *     @OA\JsonContent(
 *         @OA\Property(property="message", type="string", example="This action is unauthorized")
 *     )
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}