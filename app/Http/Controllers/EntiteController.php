<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateEntiteRequest;
use App\Http\Requests\InviteMemberRequest;
use App\Models\Entite;
use App\Models\MembreEntite;
use App\Models\User;
use App\Notifications\MemberInvitationNotification;
// use App\Notifications\PlatformInvitationNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class EntiteController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $entites = $user->entites()
            ->where('type', 'GROUPE')
            ->with(['membreEntites.user'])
            ->where('membre_entites.invite_status', 'ACCEPTED')
            ->get();

        $pendingInvites = MembreEntite::query()
            ->with('entite')
            ->where('user_id', $user->id)
            ->where('invite_status', 'INVITED')
            ->get();

        return view('entites.index', [
            'entites' => $entites,
            'pendingInvites' => $pendingInvites,
        ]);
    }

    public function store(CreateEntiteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $entite = Entite::create([
            'titre' => $data['titre'],
            'type' => 'GROUPE',
            'type_contenu' => $data['type_contenu'] ?? 'AUTRE',
        ]);

        MembreEntite::create([
            'entite_id' => $entite->id,
            'user_id' => Auth::id(),
            'role' => 'OWNER',
            'invite_status' => 'ACCEPTED',
        ]);

        return redirect()->back()->with('status', 'Entité créée.');
    }

    public function invite(InviteMemberRequest $request, Entite $entite): RedirectResponse
    {
        $this->authorizeOwner($entite);

        $data = $request->validated();
        $email = $data['email'];
        $role = $data['role'] ?? 'MEMBER';

        // Prevent inviting the same person twice
        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser !== null) {
            // Already a member?
            $already = MembreEntite::query()
                ->where('entite_id', $entite->id)
                ->where('user_id', $existingUser->id)
                ->first();

            if ($already !== null) {
                if ($already->invite_status === 'INVITED') {
                    // Re-send notification
                    $this->notifyExistingUser($already);

                    return redirect()->back()->with('status', 'Invitation renvoyée.');
                }

                return redirect()->back()->with('status', 'Cet utilisateur est déjà membre de l\'équipe.');
            }

            $membre = MembreEntite::create([
                'entite_id' => $entite->id,
                'user_id' => $existingUser->id,
                'role' => $role,
                'invite_status' => 'INVITED',
                'invited_by' => Auth::id(),
            ]);

            $this->notifyExistingUser($membre);

            return redirect()->back()->with('status', 'Invitation envoyée à l\'utilisateur existant.');
        }

        // Not on platform yet: create a user with empty password and send reset link
        $name = strstr($email, '@', true) ?: $email;
        $newUser = User::create([
            'name' => $name,
            'email' => $email,
            'password' => '', // cast 'hashed' will store a hash of empty, user must reset password
        ]);

        $membre = MembreEntite::create([
            'entite_id' => $entite->id,
            'user_id' => $newUser->id,
            'role' => $role,
            'invite_status' => 'INVITED',
            'invited_by' => Auth::id(),
        ]);

        // Notify invite (mention that a password setup email will follow)
        $this->notifyExistingUser($membre, true);
        Password::sendResetLink(['email' => $email]);

        return redirect()->back()->with('status', 'Utilisateur créé et invitation envoyée. Un email de création de mot de passe a été envoyé.');
    }

    public function acceptInvitation(Request $request, MembreEntite $membreEntite): RedirectResponse
    {
        // Optionally ensure only the intended user can accept via signed URL
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        if ($membreEntite->invite_status !== 'INVITED') {
            return redirect()->route('home')->with('status', 'Invitation déjà traitée.');
        }

        $membreEntite->invite_status = 'ACCEPTED';
        $membreEntite->save();

        return redirect()->route('home')->with('status', 'Invitation acceptée.');
    }

    public function rejectInvitation(Request $request, MembreEntite $membreEntite): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        if ($membreEntite->invite_status !== 'INVITED') {
            return redirect()->route('home')->with('status', 'Invitation déjà traitée.');
        }

        $membreEntite->invite_status = 'REJECTED';
        $membreEntite->save();

        return redirect()->route('home')->with('status', 'Invitation rejetée.');
    }

    public function cancelInvitation(Request $request, MembreEntite $membreEntite): RedirectResponse
    {
        // Only OWNER of the entité can cancel
        $entiteId = $membreEntite->entite_id;
        $isOwner = MembreEntite::query()
            ->where('entite_id', $entiteId)
            ->where('user_id', Auth::id())
            ->where('role', 'OWNER')
            ->where('invite_status', 'ACCEPTED')
            ->exists();

        if (! $isOwner) {
            abort(403, 'Action non autorisée.');
        }

        if ($membreEntite->invite_status !== 'INVITED') {
            return back()->with('status', 'Seules les invitations en attente peuvent être retirées.');
        }

        $membreEntite->delete();

        return back()->with('status', 'Invitation retirée.');
    }

    protected function authorizeOwner(Entite $entite): void
    {
        $userId = Auth::id();

        $isOwner = MembreEntite::query()
            ->where('entite_id', $entite->id)
            ->where('user_id', $userId)
            ->where('role', 'OWNER')
            ->where('invite_status', 'ACCEPTED')
            ->exists();

        if (! $isOwner) {
            abort(403, 'Action non autorisée.');
        }
    }

    protected function notifyExistingUser(MembreEntite $membre, bool $mustSetPassword = false): void
    {
        $acceptUrl = URL::temporarySignedRoute(
            'entites.members.invitations.accept',
            now()->addDays(7),
            ['membreEntite' => $membre->id]
        );

        $rejectUrl = URL::temporarySignedRoute(
            'entites.members.invitations.reject',
            now()->addDays(7),
            ['membreEntite' => $membre->id]
        );

        $membre->user->notify(new MemberInvitationNotification($membre, $acceptUrl, $rejectUrl, $mustSetPassword));
    }
}
