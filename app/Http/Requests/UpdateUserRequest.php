<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;


class UpdateUserRequest extends StoreUserRequest
{
    /**
     * Sac d'erreurs distinct de la création : seule la modale de modification se rouvre.
     *
     * @var string
     */
    protected $errorBag = 'updateUser';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $collaborator = $this->route('user');

        return [
            ...$this->accountRules($collaborator),
            'role' => [Rule::excludeIf($collaborator->is($this->user())), 'required', 'string', Rule::in($this->assignableRoles())],
        ];
    }
}
