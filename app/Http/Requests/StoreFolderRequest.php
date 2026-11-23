<?php

namespace App\Http\Requests;

use App\Models\Folder;
use App\Models\Organization;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Ouverture d'un dossier. La référence et le statut initial (« À qualifier ») sont attribués automatiquement.
 * Le créateur compose l'équipe de départ et désigne le responsable : sans cela, personne d'autre
 * ne verrait le dossier pour l'organiser.
 */
class StoreFolderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::forUser($this->user())->inspect('create', Folder::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'uuid', Rule::exists(Organization::class, 'id')->where('is_active', true)->whereNull('deleted_at')],
            ...$this->folderRules(),
            ...$this->teamRules(),
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
            'date' => 'Saisissez une date valide.',
            'uuid' => 'Choisissez une valeur dans la liste.',
            'exists' => 'Choisissez une valeur dans la liste.',
            'in' => 'Choisissez une valeur dans la liste.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'organization_id' => 'entreprise',
            'title' => 'intitulé',
            'mission_type' => 'type de mission',
            'period' => 'période',
            'priority_id' => 'priorité',
            'manager_id' => 'responsable',
            'assignee_ids' => 'équipe',
            'assignee_ids.*' => 'membre de l\'équipe',
            'due_date' => 'échéance',
            'status_id' => 'statut',
        ];
    }

    /**
     * Informations du dossier, communes à l'ouverture et à la modification.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function folderRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'mission_type' => ['required', 'string', 'max:100'],
            'period' => ['nullable', 'string', 'max:50'],
            'priority_id' => ['required', 'uuid', Rule::exists(Priority::class, 'id')],
            'due_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Équipe du dossier : des membres actifs du cabinet, dont un responsable (facultatif).
     * Le responsable est ajouté à l'équipe s'il n'y a pas été coché.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function teamRules(): array
    {
        $activeCollaborator = Rule::exists(User::class, 'id')->where('is_active', true)->whereNull('deleted_at');

        return [
            'manager_id' => ['nullable', 'uuid', $activeCollaborator],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['uuid', $activeCollaborator],
        ];
    }
}
