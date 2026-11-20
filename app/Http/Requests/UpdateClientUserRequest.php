<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Modification d'un utilisateur du portail client : mêmes règles et messages que la création,
 * sans changement d'organisation (cela ouvrirait l'accès aux dossiers d'un autre client).
 * Le nom d'utilisateur n'est pas saisi : il est recalculé si le prénom ou le nom change.
 */
class UpdateClientUserRequest extends StoreClientUserRequest
{
    /**
     * Sac d'erreurs distinct de la création : seule la modale de modification se rouvre.
     *
     * @var string
     */
    protected $errorBag = 'updateClientUser';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('client_user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->clientUserRules($this->route('client_user'));
    }
}
