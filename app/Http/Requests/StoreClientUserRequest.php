<?php

namespace App\Http\Requests;

use App\Models\ClientUser;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création d'un utilisateur du portail client. Le nom d'utilisateur et le mot de passe
 * ne sont pas saisis : ils sont générés et envoyés par e-mail.
 */
class StoreClientUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', ClientUser::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'uuid', Rule::exists(Organization::class, 'id')->whereNull('deleted_at')],
            ...$this->clientUserRules(),
        ];
    }

    /**
     * Règles communes à la création et à la modification.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function clientUserRules(?ClientUser $ignoredClientUser = null): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(ClientUser::class)->ignore($ignoredClientUser)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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
            'unique' => 'Ce :attribute est déjà utilisé par un autre utilisateur.',
            'avatar.image' => 'La photo doit être une image.',
            'avatar.mimes' => 'La photo doit être au format JPG, PNG ou WEBP.',
            'avatar.max' => 'La photo ne doit pas dépasser 2 Mo.',
            'organization_id.uuid' => 'Choisissez une organisation dans la liste.',
            'organization_id.exists' => 'Choisissez une organisation dans la liste.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'organization_id' => 'organisation',
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'email' => 'e-mail',
            'phone' => 'téléphone',
            'address' => 'adresse',
            'job_title' => 'fonction',
            'avatar' => 'photo',
        ];
    }
}
