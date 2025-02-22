<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    //
    /**
     * Create a new Sanctum token.
     * 
     * @return \Illuminate\Cookie\CookieJar|\Symfony\Component\HttpFoundation\Cookie
     */
    private function createToken(User $user): \Illuminate\Cookie\CookieJar|\Symfony\Component\HttpFoundation\Cookie
    {
        $token = $user->createToken(
            'vemer_token',
            ['*'],
            now()->addMinutes((int)config('session.lifetime'))
        )->plainTextToken;

        $user->token = $token;
        
        $cookie = cookie(
            'sinau_rek_token',
            $token,
            config('session.lifetime')
        );

        return $cookie;
    }

    public function register(RegisterRequest $request)
    {
        try {
            $user = $request->register();

            if (! $user) {
                return response()->json([
                    'message' => 'Failed to register. Please try again later.',
                ], 401);
            }

            $cookie = $this->createToken($user);

            return response()
                    ->json([
                        'user' => new UserResource($user),
                        'message' => 'Registeration Successful.',
                    ])
                    ->withCookie($cookie);
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Error registering. Please try again later.',
                    'error' => $e->getMessage(),
                ],
                500
            );
        }
    }

    public function login(LoginRequest $request)
    {  
        try {
            $user = $request->authenticate();

            if (! $user) {
                return response()->json([
                    'message' => 'Failed to login. Please try again.',
                ], 401);
            }

            $cookie = $this->createToken($user);

            return response()
                    ->json([
                        'user' => new UserResource($user),
                        'message' => 'Login Successful.',
                    ])
                    ->withCookie($cookie);
        }
        catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Error logging in. Please try again later.',
                    'error' => $e->getMessage(),
                ],
                500
            );
        }
    }
}
