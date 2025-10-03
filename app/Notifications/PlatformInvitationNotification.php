<?php

namespace App\Notifications;

use App\Models\Entite;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlatformInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private Entite $entite, private string $email) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Simple registration URL pre-filling the email - tokenless
        $url = url('/register?email='.urlencode($this->email));

        return (new MailMessage)
            ->subject('Invitation à rejoindre la plateforme')
            ->greeting('Bonjour')
            ->line('Vous êtes invité à rejoindre l\'équipe: '.$this->entite->titre)
            ->action('Créer mon compte', $url)
            ->line('Après l\'inscription, vous serez automatiquement ajouté à l\'équipe.');
    }
}
