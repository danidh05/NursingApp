<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Nursing App API Documentation",
 *     description="API documentation for the Nursing App - A platform connecting users with nursing services",
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
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}