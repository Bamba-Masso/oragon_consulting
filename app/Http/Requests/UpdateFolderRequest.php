<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Gate;

/**
 * Modification d'un dossier : mêmes règles que l'ouverture, sans changement d'entreprise.
 * L'équipe et le responsable ne sont pris en compte que pour un collaborateur qui a le droit d'affecter.
 * Le statut n'est pas modifiable ici pour l'instant : il suivra le parcours du dossier, à définir.
 */
class UpdateFolderRequest extends StoreFolderRequest
{
    /**
     * Sac d'erreurs distinct de l'ouverture : seule la modale de modification se rouvre.
     *
     * @var string
     */
    protected $errorBag = 'updateFolder';

    /**
     * Determine if the user is authorized to make this request.
     * La réponse complète de la Policy est renvoyée pour conserver le 404 d'un dossier hors périmètre.
     */
    public function authorize(): Response
    {
        return Gate::forUser($this->user())->inspect('update', $this->route('folder'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->folderRules(),
            ...($this->canAssignTeam() ? $this->teamRules() : []),
        ];
    }

    /**
     * Modifier l'équipe ou le responsable exige le droit d'affectation (FolderPolicy::assign) ;
     * sans lui, ces champs sont ignorés.
     */
    public function canAssignTeam(): bool
    {
        return $this->user()->can('assign', $this->route('folder'));
    }
}
