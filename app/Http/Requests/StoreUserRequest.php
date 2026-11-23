<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création d'un collaborateur du cabinet. Le nom d'utilisateur et le mot de passe
 * ne sont pas saisis : ils sont générés et envoyés par e-mail.
 */
class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->accountRules(),
            'role' => ['required', 'string', Rule::in($this->assignableRoles())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Le champ « :attribute » est obligatoire.',
            'max' => 'Le champ « :attribute » ne doit pas dépasser :max caractères.',
            'email' => 'Saisissez une adresse e-mail valide.',
            'unique' => 'Cet :attribute est déjà utilisé par un autre collaborateur.',
            'role.in' => 'Choisissez un rôle dans la liste.',
            'avatar.image' => 'La photo doit être une image.',
            'avatar.mimes' => 'La photo doit être au format JPG, PNG ou WEBP.',
            'avatar.max' => 'La photo ne doit pas dépasser 2 Mo.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'email' => 'e-mail',
            'phone' => 'téléphone',
            'address' => 'adresse',
            'role' => 'rôle',
            'avatar' => 'photo',
        ];
    }

    /**
     * Règles communes à la création et à la modification.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function accountRules(?User $ignoredUser = null): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($ignoredUser)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Rôles que le collaborateur connecté a le droit d'attribuer.
     *
     * @return list<string>
     */
    protected function assignableRoles(): array
    {
        return array_values(array_filter(
            array_keys(Role::CABINET_LABELS),
            fn (string $role): bool => $this->user()->can('assignRole', [User::class, $role]),
        ));
    }
}
