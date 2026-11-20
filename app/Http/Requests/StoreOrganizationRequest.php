<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Organization::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->organizationRules();
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
            'in' => 'Choisissez une forme juridique dans la liste.',
            'unique' => 'Ce :attribute est déjà utilisé par une autre organisation.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'raison sociale',
            'legal_form' => 'forme juridique',
            'registration_number' => 'numéro RCCM',
            'tax_number' => 'numéro de compte contribuable',
            'email' => 'e-mail',
            'phone' => 'téléphone',
            'address' => 'adresse',
            'city' => 'ville',
        ];
    }

    /**
     * Règles communes à la création et à la modification.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function organizationRules(?Organization $ignoredOrganization = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_form' => ['required', 'string', Rule::in(Organization::LEGAL_FORMS)],
            'registration_number' => ['required', 'string', 'max:255', Rule::unique(Organization::class)->ignore($ignoredOrganization)],
            'tax_number' => ['required', 'string', 'max:255', Rule::unique(Organization::class)->ignore($ignoredOrganization)],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
        ];
    }
}
