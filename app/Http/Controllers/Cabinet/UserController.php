<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\StaffAccessCredentials;
use App\Mail\StaffAccessUpdated;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Gestion des collaborateurs du cabinet (même fonctionnement que les utilisateurs clients,
 * avec en plus le rôle du collaborateur).
 */
class UserController extends Controller
{
    /**
     * Statuts proposés dans le filtre de la liste.
     *
     * @var array<string, string>
     */
    private const STATUS_FILTERS = ['active' => 'Actifs', 'inactive' => 'Désactivés'];

    /**
     * Dossier des photos de profil sur le disque « public ».
     */
    private const AVATAR_DIRECTORY = 'user-avatars';

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $search = $request->string('search')->trim()->toString();
        $role = $request->string('role')->toString();
        $status = $request->string('status')->toString();

        $collaborators = User::query()
            ->with('roles')
            ->when($search !== '', fn (Builder $query) => $query->whereAny(
                ['first_name', 'last_name', 'email', 'username'],
                'like',
                "%{$search}%",
            ))
            ->when(array_key_exists($role, Role::CABINET_LABELS), fn (Builder $query) => $query->role($role))
            ->when($status === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('cabinet.users.index', [
            'collaborators' => $collaborators,
            'roles' => Role::CABINET_LABELS,
            'assignableRoles' => $this->assignableRoles($request),
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'statusFilters' => self::STATUS_FILTERS,
        ]);
    }

   
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $input = Arr::except($request->validated(), ['avatar', 'role']);
        $input['avatar_path'] = $request->file('avatar')?->store(self::AVATAR_DIRECTORY, 'public');
        $temporaryPassword = Str::password(12, symbols: false);

        $collaborator = DB::transaction(function () use ($input, $temporaryPassword, $request): User {
            $input['password'] = $temporaryPassword;
            $input['username'] = User::generateUsername($input['first_name'], $input['last_name']);

            $collaborator = new User($input);
            $collaborator->email_verified_at = now();
            $collaborator->save();
            $collaborator->syncRoles([$request->validated('role')]);

            return $collaborator;
        });

        try {
            Mail::to($collaborator->email, $collaborator->full_name)
                ->send(new StaffAccessCredentials($collaborator, $temporaryPassword));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return to_route('cabinet.users.index')->with('error',
                "Le compte de {$collaborator->full_name} a été créé, mais l'e-mail d'accès n'a pas pu être envoyé. Vérifiez la configuration de la messagerie.");
        }

        return to_route('cabinet.users.index')->with('status',
            "Le compte de {$collaborator->full_name} a été créé. Ses accès ont été envoyés à {$collaborator->email}.");
    }

    /**
     * Fiche complète d'un collaborateur.
     */
    public function show(Request $request, User $user): View
    {
        Gate::authorize('view', $user);

        return view('cabinet.users.show', [
            'collaborator' => $user->load('roles'),
            'assignableRoles' => $this->assignableRoles($request),
        ]);
    }

    /**
     * Le formulaire est une modale (vue cabinet.users.edit), sur la liste et sur la fiche :
     * on revient à la page d'origine.
     *
     * Si le prénom ou le nom change, le nom d'utilisateur est recalculé. Si le nom d'utilisateur
     * ou l'e-mail change, les nouveaux identifiants sont envoyés par e-mail (le mot de passe ne change pas).
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill(Arr::except($request->validated(), ['avatar', 'role']));

        if ($user->isDirty(['first_name', 'last_name'])) {
            $user->username = User::generateUsername($user->first_name, $user->last_name, $user);
        }

        $accessChanged = $user->isDirty(['username', 'email']);

        if ($request->hasFile('avatar')) {
            $previousAvatar = $user->avatar_path;
            $user->avatar_path = $request->file('avatar')->store(self::AVATAR_DIRECTORY, 'public');
        }

        DB::transaction(function () use ($request, $user): void {
            $user->save();

            if ($request->validated('role')) {
                $user->syncRoles([$request->validated('role')]);
            }
        });

        if (isset($previousAvatar)) {
            Storage::disk('public')->delete($previousAvatar);
        }

        if (! $accessChanged) {
            return back()->with('status', "Les informations de {$user->full_name} ont été enregistrées.");
        }

        try {
            Mail::to($user->email, $user->full_name)->send(new StaffAccessUpdated($user));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return back()->with('error',
                "Les informations de {$user->full_name} ont été enregistrées, mais l'e-mail avec ses nouveaux accès n'a pas pu être envoyé. Vérifiez la configuration de la messagerie.");
        }

        return back()->with('status',
            "Les informations de {$user->full_name} ont été enregistrées. Ses nouveaux accès ont été envoyés à {$user->email}.");
    }

    /**
     * Désactive ou réactive le compte. Désactivé, le collaborateur ne peut plus se connecter.
     */
    public function toggleActivation(User $user): RedirectResponse
    {
        Gate::authorize($user->is_active ? 'lock' : 'unlock', $user);

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('status', $user->is_active
            ? "Le compte de {$user->full_name} a été réactivé."
            : "Le compte de {$user->full_name} a été désactivé : il ne peut plus se connecter.");
    }

    /**
     * Rôles que le collaborateur connecté peut attribuer, avec leur libellé.
     *
     * @return Collection<string, string>
     */
    private function assignableRoles(Request $request): Collection
    {
        return collect(Role::CABINET_LABELS)
            ->filter(fn (string $label, string $role): bool => $request->user()->can('assignRole', [User::class, $role]));
    }
}
