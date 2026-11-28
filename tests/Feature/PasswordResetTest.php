<?php

namespace Tests\Feature;

use App\Mail\PasswordResetLink;
use App\Models\ClientUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const RESET_LINK_SENT_MESSAGE = 'Si un compte actif correspond à cette adresse, vous allez recevoir un e-mail avec un lien pour choisir un nouveau mot de passe.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Mail::fake();
    }

    public function test_the_login_pages_link_to_the_forgot_password_page_of_their_space(): void
    {
        $this->get(route('cabinet.login'))->assertOk()->assertSee(route('cabinet.password.request'));
        $this->get(route('client.login'))->assertOk()->assertSee(route('client.password.request'));

        $this->get(route('cabinet.password.request'))->assertOk()->assertSee('Espace collaborateur')->assertSee('Envoyer le lien');
        $this->get(route('client.password.request'))->assertOk()->assertSee('Espace client')->assertSee('Envoyer le lien');
    }

    public function test_an_active_collaborator_receives_a_reset_link(): void
    {
        $user = User::factory()->create();

        $this->from(route('cabinet.password.request'))
            ->post(route('cabinet.password.email'), ['email' => $user->email])
            ->assertRedirect(route('cabinet.password.request'))
            ->assertSessionHas('status', self::RESET_LINK_SENT_MESSAGE);

        Mail::assertSent(PasswordResetLink::class, fn (PasswordResetLink $mail): bool => $mail->hasTo($user->email)
            && $mail->account->is($user));
    }

    public function test_unknown_and_inactive_accounts_get_the_same_answer_without_any_email(): void
    {
        $inactiveClientUser = ClientUser::factory()->inactive()->create();

        foreach (['inconnu@entreprise.ci', $inactiveClientUser->email] as $email) {
            $this->from(route('client.password.request'))
                ->post(route('client.password.email'), ['email' => $email])
                ->assertRedirect(route('client.password.request'))
                ->assertSessionHas('status', self::RESET_LINK_SENT_MESSAGE);
        }

        Mail::assertNothingSent();
    }

    public function test_a_client_user_can_choose_a_new_password_from_the_link(): void
    {
        $clientUser = ClientUser::factory()->create();
        $token = Password::broker('client_users')->createToken($clientUser);

        $this->get(route('client.password.reset', ['token' => $token, 'email' => $clientUser->email]))
            ->assertOk()
            ->assertSee($clientUser->email);

        $this->post(route('client.password.update'), [
            'token' => $token,
            'email' => $clientUser->email,
            'password' => 'NouveauMotDePasse1',
            'password_confirmation' => 'NouveauMotDePasse1',
        ])->assertRedirect(route('client.login'))
            ->assertSessionHas('status', 'Votre mot de passe a été modifié. Vous pouvez maintenant vous connecter.');

        $this->assertTrue(Hash::check('NouveauMotDePasse1', $clientUser->fresh()->password));

        $this->post(route('client.login.store'), ['login' => $clientUser->email, 'password' => 'NouveauMotDePasse1'])
            ->assertRedirect(route('client.dashboard'));
    }

    public function test_an_invalid_or_other_space_token_does_not_change_the_password(): void
    {
        $user = User::factory()->create();
        $clientToken = Password::broker('client_users')->createToken(ClientUser::factory()->create(['email' => $user->email]));

        foreach (['jeton-invalide', $clientToken] as $token) {
            $this->from(route('cabinet.password.reset', ['token' => $token]))
                ->post(route('cabinet.password.update'), [
                    'token' => $token,
                    'email' => $user->email,
                    'password' => 'NouveauMotDePasse1',
                    'password_confirmation' => 'NouveauMotDePasse1',
                ])->assertSessionHasErrors(['email' => 'Ce lien est invalide ou a expiré. Faites une nouvelle demande de réinitialisation.']);
        }

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_the_reset_email_contains_the_link_of_the_right_space(): void
    {
        $collaborator = User::factory()->create(['first_name' => 'Awa']);

        $mail = new PasswordResetLink($collaborator, 'jeton-de-test');

        $mail->assertHasSubject('Réinitialisation de votre mot de passe Oragon Consulting');
        $mail->assertSeeInHtml('Bonjour Awa');
        $mail->assertSeeInHtml(route('cabinet.password.reset', ['token' => 'jeton-de-test', 'email' => $collaborator->email]));
        $mail->assertSeeInHtml('valable 60 minutes');
    }
}
