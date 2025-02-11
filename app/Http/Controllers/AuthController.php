<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Validator;

class AuthController extends Controller
{
  /**
   * Create a new AuthController instance.
   *
   * @return void
   */
  public function __construct() {
      $this->middleware('auth:api', ['except' => ['login', 'register', 'unauthorized']]);
  }

  /**
   * Get a JWT via given credentials.
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function login(Request $request){
    $validator = Validator::make($request->all(), [
          'email' => 'required|email',
          'password' => 'required|string|min:6',
      ]);

      if ($validator->fails()) {
          return response()->json($validator->errors(), 422);
      }

      if (! $token = auth()->attempt($validator->validated())) {
          return response()->json([
            'message' => 'Unauthorized Credentials!',
            'status' => false
          ], 401);
      }

      if (auth()->user()->type !== 'admin') {
        return response()->json([
          'message' => 'Unauthorized Credentials!',
          'status' => false
        ], 400);
      }

      return $this->createNewToken($token);
  }

  /**
   * Register a User.
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function register(Request $request) {
      $validator = Validator::make($request->all(), [
          'name' => 'required|string|between:2,100',
          'email' => 'required|string|email|max:100|unique:users',
          'password' => 'required|string|confirmed|min:6'
      ]);

      if($validator->fails()){
          return response()->json([
              'message' => 'User unsuccessfully registered',
              'error' => $validator->errors()->toJson(),
              'status' => false
          ], 400);
      }
      $user = User::create(array_merge(
                  $validator->validated(),
                  ['password' => bcrypt($request->password), 'type' => 'customer']
              ));

      return response()->json([
          'message' => 'User successfully registered',
          'user' => $user,
          'status' => true
      ], 201);
  }


  /**
   * Log the user out (Invalidate the token).
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function unauthorized() {
    return response()->json([
      'message' => 'Unauthorized Credentials!',
      'status' => false,
      'login' => true
    ], 401);
  }
  /**
   * Log the user out (Invalidate the token).
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function logout() {
      auth()->logout();

      return response()->json([
        'message' => 'User successfully signed out',
        'status' => true
    ]);
  }

  /**
   * Refresh a token.
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function refresh() {
      return $this->createNewToken(auth()->refresh());
  }

  /**
   * Get the authenticated User.
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function userProfile() {
      return response()->json(auth()->user());
  }

  /**
   * Get the token array structure.
   *
   * @param  string $token
   *
   * @return \Illuminate\Http\JsonResponse
   */
  protected function createNewToken($token){
      return response()->json([
          'access_token' => $token,
          'token_type' => 'bearer',
          'expires_in' => auth()->factory()->getTTL() * 60,
          'user' => auth()->user(),
          'status' => true
      ]);
  }
}
