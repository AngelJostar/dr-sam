<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterKliniRequest extends FormRequest
{
    protected $errorBag = 'registration';
    protected $redirectRoute = 'home';

    public function authorize(): bool
    {
        return $this->user() === null;
    }

    protected function prepareForValidation(): void
    {
        foreach (['email', 'username'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => mb_strtolower(trim($this->input($field)))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'registration_type' => ['required', Rule::in(['patient', 'doctor'])],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:180', Rule::unique('users', 'email')],
            'username' => ['required', 'string', 'min:3', 'max:60', 'regex:/\A[a-z0-9][a-z0-9._-]*\z/', Rule::unique('users', 'username')],
            'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(10)->letters()->numbers()],
            'professional_license' => ['exclude_unless:registration_type,doctor', 'required', 'string', 'max:80'],
            'specialty' => ['exclude_unless:registration_type,doctor', 'required', 'string', 'max:120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'registration_type' => 'tipo de cuenta', 'first_name' => 'nombre', 'last_name' => 'apellidos',
            'email' => 'correo electrónico', 'username' => 'usuario', 'password' => 'contraseña',
            'professional_license' => 'cédula profesional', 'specialty' => 'especialidad',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Completa el campo :attribute.', 'email.email' => 'Escribe un correo electrónico válido.',
            'unique' => 'Este :attribute ya está registrado.', 'registration_type.in' => 'Selecciona Paciente o Médico.',
            'username.regex' => 'El usuario admite letras sin acentos, números, punto, guion y guion bajo.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute admite hasta :max caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.letters' => 'La contraseña debe incluir letras.',
            'password.numbers' => 'La contraseña debe incluir números.',
        ];
    }
}
