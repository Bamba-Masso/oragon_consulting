<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientUserRequest;
use App\Http\Requests\UpdateClientUserRequest;
use App\Mail\ClientAccessCredentials;
use App\Mail\ClientAccessUpdated;
use App\Models\ClientUser;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Gestion des utilisateurs du portail client par le cabinet.
 */
class ClientUserController extends Controller
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
    private const AVATAR_DIRECTORY = 'client-avatars';

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ClientUser::class);

        $search = $request->string('search')->trim()->toString();
        $organizationId = $request->string('organization')->toString();
        $status = $request->string('status')->toString();

        $clientUsers = ClientUser::query()
            ->with('organization')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereAny(['first_name', 'last_name', 'email', 'username'], 'like', "%{$search}%")
                ->orWhereRelation('organization', 'name', 'like', "%{$search}%")))
            ->when($organizationId !== '', fn (Builder $query) => $query->where('organization_id', $organizationId))
            ->when($status === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('cabinet.client-users.index', [
            'clientUsers' => $clientUsers,
            'organizations' => Organization::query()->orderBy('name')->pluck('name', 'id'),
            'activeOrganizations' => Organization::query()->active()->orderBy('name')->pluck('name', 'id'),
            'search' => $search,
            'organizationId' => $organizationId,
            'status' => $status,
            'statusFilters' => self::STATUS_FILTERS,
        ]);
    }

       public function store(StoreClientUserRequest $request): RedirectResponse
    {
        $input = Arr::except($request->validated(), 'avatar');
        $input['avatar_path'] = $request->file('avatar')?->store(self::AVATAR_DIRECTORY, 'public');
        $temporaryPassword = Str::password(12, symbols: false);

        $clientUser = DB::transaction(function () use ($input, $temporaryPassword): ClientUser {

            $input['password'] = $temporaryPassword;
            $input['username'] = ClientUser::generateUsername($input['first_name'], $input['last_name']);
            $input['created_by'] = Auth::id();
            $input['updated_by'] = Auth::id();
            $clientUser = new ClientUser($input);
            $clientUser->save();
            $clientUser->assignRole('client');

            return $clientUser;
        });

        try {
            Mail::to($clientUser->email, $clientUser->full_name)
                ->send(new ClientAccessCredentials($clientUser, $temporaryPassword));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return to_route('cabinet.client-users.index')->with('error',
                "Le compte de {$clientUser->full_name} a été créé, mais l'e-mail d'accès n'a pas pu être envoyé. Vérifiez la configuration de la messagerie.");
        }

        return to_route('cabinet.client-users.index')->with('status',
            "Le compte de {$clientUser->full_name} a été créé. Ses accès ont été envoyés à {$clientUser->email}.");
    }

    /**
     * Fiche complète d'un utilisateur du portail.
     */
    public function show(ClientUser $clientUser): View
    {
        Gate::authorize('view', $clientUser);

        return view('cabinet.client-users.show', [
            'clientUser' => $clientUser->load(['organization', 'creator']),
        ]);
    }

   
    public function update(UpdateClientUserRequest $request, ClientUser $clientUser): RedirectResponse
    {
        $clientUser->fill(Arr::except($request->validated(), 'avatar'));

        if ($clientUser->isDirty(['first_name', 'last_name'])) {
            $clientUser->username = ClientUser::generateUsername($clientUser->first_name, $clientUser->last_name, $clientUser);
        }

        $accessChanged = $clientUser->isDirty(['username', 'email']);

        if ($request->hasFile('avatar')) {
            $previousAvatar = $clientUser->avatar_path;
            $clientUser->avatar_path = $request->file('avatar')->store(self::AVATAR_DIRECTORY, 'public');
        }

        $clientUser->updated_by = Auth::id();
        $clientUser->save();

        if (isset($previousAvatar)) {
            Storage::disk('public')->delete($previousAvatar);
        }

        if (! $accessChanged) {
            return back()->with('status', "Les informations de {$clientUser->full_name} ont été enregistrées.");
        }

        try {
            Mail::to($clientUser->email, $clientUser->full_name)->send(new ClientAccessUpdated($clientUser));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return back()->with('error',
                "Les informations de {$clientUser->full_name} ont été enregistrées, mais l'e-mail avec ses nouveaux accès n'a pas pu être envoyé. Vérifiez la configuration de la messagerie.");
        }

        return back()->with('status',
            "Les informations de {$clientUser->full_name} ont été enregistrées. Ses nouveaux accès ont été envoyés à {$clientUser->email}.");
    }

    /**
     * Désactive ou réactive l'accès au portail. Désactivé, l'utilisateur ne peut plus se connecter.
     */
    public function toggleActivation(Request $request, ClientUser $clientUser): RedirectResponse
    {
        Gate::authorize($clientUser->is_active ? 'lock' : 'unlock', $clientUser);

        $clientUser->is_active = ! $clientUser->is_active;
        $clientUser->updated_by = Auth::id();
        $clientUser->save();

        return back()->with('status', $clientUser->is_active
            ? "L'accès de {$clientUser->full_name} a été réactivé."
            : "L'accès de {$clientUser->full_name} a été désactivé : il ne peut plus se connecter.");
    }
}
