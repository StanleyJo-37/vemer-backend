<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

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
            'vemer_token',
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

    public function loginSSO(Request $request) {
        try {
            $request->validate([
                'provider' => 'string|required|in:google,linkedin-openid',
                'web_origin' => 'string',
                'target_path' => 'string',
            ]);

            $oauthUrl = Socialite::driver($request->provider)->stateless()->redirect()->getTargetUrl();

            return response()->json([
                'redirect_url' => $oauthUrl . "&state=" . urlencode("web_origin=$request->web_origin&target_path=$request->target_path"),
            ]);
        } catch (Exception $e) {
            // DB::rollBack();
            return response()->json(
                [
                    'message' => 'Error logging in. Please try again later.',
                    'error' => $e->getMessage(),
                ],
                500
            );
        }
    }

    public function callbackSSO(string $provider, Request $request) {
        try {
            DB::beginTransaction();

            if (! in_array($provider, ['google', 'facebook', 'x', 'linkedin-openid',])) {
                return response()->json("Provider not found or not used.", 403);
            }

            $socialUser = Socialite::driver($provider)->stateless()->user();

            $user = User::updateOrCreate(
                ['email' => $socialUser->getEmail()],  // Search by email
                [
                    'name' => $socialUser->getName(),
                    'email_verified_at' => now(), // Mark email as verified
                    'password' => bcrypt(uniqid()), // Random password (not used)
                    'avatar' => $socialUser->getAvatar(), // If your model has an avatar field
                    'provider' => $request->provider,
                    'provider_id' => $socialUser->getId(),
                ]
            );

            $cookie = $this->createToken($user);

            DB::commit();

            return response()
                    ->json([
                        'user' => new UserResource($user),
                        'message' => 'Login Successful.',
                    ])
                    ->withCookie($cookie);
        } catch (Exception $e) {
            DB::rollBack();
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
