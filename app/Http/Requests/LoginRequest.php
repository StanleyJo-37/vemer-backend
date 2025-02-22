<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LoginRequest extends FormRequest
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
            'remember' => 'boolean|nullable',
        ];
    }

    /**
     * Get the user credentials.
     *
     * @return array<string>
     */
    public function credentials(): array
    {
        return $this->only('email', 'password');
    }

    private function failedLogin()
    {
        
    }

    /**
     * Authenticate the user.
     *
     * @return ?User
     */
    public function authenticate(): ?User
    {
        if (! Auth::attempt($this->validated(), $this->boolean("remember"))) {
            $this->failedLogin();
            return null;
        }
        
        return User::find(Auth::id());
    }

    /**
     * Get the validated data.
     *
     * @return array<mixed>
     */
    public function validatedData(): array
    {
        return $this->only('email', 'password');
    }
}
