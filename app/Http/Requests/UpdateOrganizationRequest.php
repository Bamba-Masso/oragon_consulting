<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Mêmes règles et messages que la création, en ignorant l'organisation modifiée
 * pour les contrôles d'unicité.
 */
class UpdateOrganizationRequest extends StoreOrganizationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('organization'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->organizationRules($this->route('organization'));
    }
}
