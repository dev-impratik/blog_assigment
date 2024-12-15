<?php

namespace App\Http\Controllers;
  
use App\Http\Controllers\Controller;
use App\Models\User;
use Validator;
use App\Http\Requests\LoginRequest;

  
  
class AuthController extends Controller
{
 
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     operationId="register",
     *     tags={"Authentication"},
     *     summary="User registration",
     *     description="Register a new user with name, username, email, and password.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","username","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="username", type="string", example="johndoe123"),
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Successful registration",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User registered successfully."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="email", type="string")
     *             ),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="boolean", example=true),
     *             @OA\Property(property="message", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */

    public function register() {
        $validator = Validator::make(request()->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|min:6|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
        ]);
    
        if ($validator->fails()) {
            return response()->json(["success"=> false, "error"=> true, "message" => $validator->errors()], 400);
        }
    
        try {
            $userData = request()->only('name', 'email', 'username', 'password');
            $userData['password'] = bcrypt($userData['password']);
            
            $user = User::create($userData);
    
            // Assign default role
            try {
                $user->assignRole('user');
            } catch (\Exception $e) {
                return response()->json(["success"=> false, "error"=> true, "message" => "Role assignment failed"], 500);
            }
    
            return response()->json([
                'message' => 'User registered successfully.',
                'user' => $user,
                "success"=> true,
                "error"=> false
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json(["success"=> false, "error"=> true, "message" => "User registration failed."], 500);
        }
    }
    
    
  
  
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     operationId="login",
     *     tags={"Authentication"},
     *     summary="User login",
     *     description="User login and JWT token generation.  `login` using email or username",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login","password"},
     *             @OA\Property(property="login", type="string", format="email or string"),
     *             @OA\Property(property="password", type="string", format="password")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login with JWT token",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string"),
     *             @OA\Property(property="expires_in", type="integer"),
     *             @OA\Property(property="token_created_at", type="datetime")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        $field = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $request->merge([$field => $request->input('login')]);
        $credentials = $request->only($field, 'password');
        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Incorrect username or password'], 401);
        }
        return $this->respondWithToken($token);

    }
  
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Post(
     *     path="/api/auth/me",
     *     operationId="me",
     *     tags={"Authentication"},
     *     summary="Get authenticated user details",
     *     description="Retrieve details of the currently authenticated user.",
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated user details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="username", type="string"),
     *             @OA\Property(property="email", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */

    public function me()
    {
        return response()->json(auth()->user());
    }
  
    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     operationId="logout",
     *     tags={"Authentication"},
     *     summary="Logout the user",
     *     description="Logout the currently authenticated user and invalidate the JWT token.",
     *     @OA\Response(
     *         response=200,
     *         description="Successful logout",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */

    public function logout()
    {
        auth()->logout();
  
        return response()->json(['message' => 'Successfully logged out'], 200);
    }
  
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     operationId="refresh",
     *     tags={"Authentication"},
     *     summary="Refresh JWT token",
     *     description="Refresh the JWT token for the authenticated user.",
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer"),
     *             @OA\Property(property="token_created_at", type="datetime")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }
  
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'token_created_at' => now()->toDateTimeString()
        ]);
    }
}