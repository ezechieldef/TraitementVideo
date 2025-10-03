<?php

namespace App\Notifications;

use App\Models\MembreEntite;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private MembreEntite $membre,
        private string $acceptUrl,
        private string $rejectUrl,
        private bool $mustSetPassword = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Invitation à rejoindre une équipe')
            ->greeting('Bonjour '.($notifiable->name ?? ''))
            ->line('Vous avez été invité à rejoindre l\'équipe: '.$this->membre->entite->titre)
            ->action('Accepter', $this->acceptUrl)
            ->line('Ou bien:')
            ->action('Refuser', $this->rejectUrl)
            ->line('Si vous pensez que cela est une erreur, ignorez ce message.');

        if ($this->mustSetPassword) {
            $mail->line('Note: Un email séparé vient de vous être envoyé afin de définir votre mot de passe et accéder à votre compte.');
        }

        return $mail;
    }
}
