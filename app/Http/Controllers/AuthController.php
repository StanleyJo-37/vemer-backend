<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            config('session.lifetime'),
            '/',
            config('session.domain'),
//            config('app.env') === 'production' || config('app.env') === 'staging',
            true,
            false,
//            config('app.env') === 'production' || config('app.env') === 'staging' ? 'none' : 'lax'
        );

        Log::info('Cookie created: ' . json_encode([
            'name' => 'vemer_token',
            'value' => $token,
            'expires' => config('session.lifetime'),
            'path' => '/',
            'domain' => config('session.domain'),
//            'secure' => config('app.env') === 'production' || config('app.env') === 'staging',
            'httpOnly' => true,
//            'sameSite' => config('app.env') === 'production' || config('app.env') === 'staging' ? 'none' : 'lax'
        ]));

        return $cookie;
    }

    public function register(RegisterRequest $request)
    {
        try {
            if (User::where('email', $request->email)->exists()) {
                abort(422, "User already exists.");
            }

            $user = $request->register();

            if (!$user) {
                return response()->json([
                    'message' => 'Failed to register. Please try again later.',
                ], 401);
            }

            $cookie = $this->createToken($user);

            Auth::login($user);

            return response()->json(new UserResource($user))
                            ->withCookie($cookie)
                            ->header('Access-Control-Allow-Credentials', 'true');
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function isPublisher(Request $request) {
        try {
            Log::info('EEROROOROOROORORO ');
            $user_id = Auth::id();
            Log::info('EEROROOROOROORORO ' . $user_id);
            $validatedRequest = $request->validate([
                'is_publisher' => 'required|boolean',
            ]);

            DB::table('users')
                ->where('id', $user_id)
                ->update(['is_publisher' => $validatedRequest['is_publisher']]);

            return response()->json(['message' => 'Publisher status updated successfully.'], 200);
        } catch (Exception $e) {
            throw $e;
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

            Auth::login($user);

            return response()->json(new UserResource($user))
                            ->withCookie($cookie)
                            ->header('Access-Control-Allow-Credentials', 'true');
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    public function loginSSO(Request $request) {
        try {
            $request->validate([
                'provider' => 'string|required|in:google',
                // 'web_origin' => 'string',
                // 'target_path' => 'string',
            ]);

            $oauthUrl = Socialite::driver($request->provider)->stateless()->redirect()->getTargetUrl();

            return response()->json([
                'redirect_url' => $oauthUrl . "&state=" . urlencode("web_origin=$request->web_origin&target_path=$request->target_path"),
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function callbackSSO(string $provider, Request $request) {
        try {
            DB::beginTransaction();

            if (! in_array($provider, ['google', 'linkedin-openid',])) {
                return response()->json("Provider not found or not used.", 403);
            }

            $socialUser = Socialite::driver($provider)->stateless()->user();

            $user = User::updateOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name' => $socialUser->getName(),
                    'username' => $socialUser->getName(),
                    'email_verified_at' => now(),
                    'password' => bcrypt(uniqid()),
                    'avatar' => $socialUser->getAvatar(),
                    'provider' => $request->provider,
                    'provider_id' => $socialUser->getId(),
                ]
            );

            $cookie = $this->createToken($user);

            Auth::login($user);

            DB::commit();

            parse_str($request->query('state', ''), $state);
            $targetPath = $state['target_path'] ?? '/dashboard';
            $webOrigin = $state['web_origin'] ?? 'http://localhost:3000';

            $userResource = new UserResource($user);
            $script = "
                        <script>
                            window.opener.postMessage({
                                user: " . json_encode($userResource) . ",
                                targetPath: " . json_encode($targetPath) . ",
                                error: null
                            }, '*');
                            window.close();
                        </script>
                    ";

            return response($script, 200)->header('Content-Type', 'text/html')
                                        ->header('Access-Control-Allow-Credentials', 'true')
                                        ->withCookie($cookie);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function logout(Request $request) {
        try {
            DB::beginTransaction();

            $request->user()->currentAccessToken()->delete();

            $token_cookie = cookie('vemer_token', null, -2628000, null, null);
            return response()->json([
                                'message' => 'Logged out successfully!'
                            ])
                            ->header('Access-Control-Allow-Credentials', 'true')
                            ->withCookie($token_cookie);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
