<?php

namespace App\Http\Requests;

use App\Models\Folder;
use App\Models\Priority;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Ajout d'une tâche depuis la fiche du dossier. La tâche est assignée à un membre de l'équipe du dossier.
 */
class StoreTaskRequest extends FormRequest
{
    /**
     * Sac d'erreurs propre au formulaire de tâche : seule sa modale se rouvre.
     *
     * @var string
     */
    protected $errorBag = 'storeTask';

    /**
     * Determine if the user is authorized to make this request.
     * La réponse complète de la Policy est renvoyée pour conserver le 404 d'un dossier hors périmètre.
     */
    public function authorize(): Response
    {
        return Gate::forUser($this->user())->inspect('addTask', $this->folder());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'uuid', Rule::in($this->folder()->assignees()->pluck('users.id')->all())],
            'priority_id' => ['required', 'uuid', Rule::exists(Priority::class, 'id')],
            'due_date' => ['nullable', 'date'],
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
            'assigned_to.in' => 'Choisissez un membre de l\'équipe du dossier.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'intitulé',
            'description' => 'description',
            'assigned_to' => 'personne en charge',
            'priority_id' => 'priorité',
            'due_date' => 'échéance',
        ];
    }

    protected function folder(): Folder
    {
        return $this->route('folder');
    }
}
