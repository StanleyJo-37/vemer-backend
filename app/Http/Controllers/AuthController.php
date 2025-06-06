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
            if (User::where('email', $request->email)->exists()) {
                abort(422, "User already exists.");
            }

            $user = $request->register();

            if (! $user) {
                return response()->json([
                    'message' => 'Failed to register. Please try again later.',
                ], 401);
            }

            $cookie = $this->createToken($user);

            return response()->json(new UserResource($user))
                            ->withCookie($cookie);
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

            return response()->json(new UserResource($user))
                            ->withCookie($cookie);
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    public function loginSSO(Request $request) {
        try {
            $request->validate([
                'provider' => 'string|required|in:google,linkedin-openid',
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
                                        ->withCookie($cookie);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
