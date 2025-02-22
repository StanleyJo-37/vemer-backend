<?php

namespace App\Http\Requests;

use App\Models\User;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'email' => 'email|required',
            'password' => 'string|min:8|required',
            'name' => 'string|min:8|max:24|alpha_num|required',
            'profile_photo_path' => 'string|nullable',
            'remember' => 'boolean',
        ];
    }

    /**
     * Get the validated data.
     *
     * @return array<mixed>
     */
    public function credentials(): array
    {
        return $this->only('email', 'password', 'name', 'profile_photo_path');
    }

    /**
     * Register a new user.
     * 
     * @return ?User
     */
    public function register(): ?User
    {
        try {
            $credentials = $this->validated();

            $user = User::create([
                'email' => $credentials['email'],
                'password' => Hash::make($credentials['password']),
                'name' => $credentials['name'],
                'profile_photo_path' => array_key_exists('profile_photo_path', $credentials) ? $credentials['profile_photo_path'] : null,
            ]);

            Auth::login($user, $this->boolean('remember'));
            
            return $user;
        }
        catch (Exception $e) {
            dd($e);
            return null;
        }
    }
}
