<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

/**
 * Modification d'une tâche depuis la fiche du dossier : mêmes règles que l'ajout.
 * Changer la personne en charge exige le droit d'assigner les tâches ; sans lui, ce champ est ignoré.
 */
class UpdateTaskRequest extends StoreTaskRequest
{
    /**
     * Sac d'erreurs propre à la modification : seule la modale de la tâche modifiée se rouvre.
     *
     * @var string
     */
    protected $errorBag = 'updateTask';

    /**
     * Determine if the user is authorized to make this request.
     * La réponse complète de la Policy est renvoyée pour conserver le 404 d'un dossier hors périmètre.
     */
    public function authorize(): Response
    {
        return Gate::forUser($this->user())->inspect('updateTask', $this->folder());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        return $this->canAssignTask() ? $rules : Arr::except($rules, 'assigned_to');
    }

    public function canAssignTask(): bool
    {
        return $this->user()->can('assignTask', $this->folder());
    }
}
