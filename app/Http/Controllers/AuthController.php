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
    public function me()
    {
        return response()->json(auth()->user());
    }
  
    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
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
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}