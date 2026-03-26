<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamStatusNotification extends Notification
{
    use Queueable;

    private $details;

    public function __construct($details)
    {
        $this->details = $details;
    }

    public function via($notifiable)
    {
        return ['mail', 'database']; // Kirim ke email dan simpan di database dashboard
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->details['subject'])
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line($this->details['message'])
            ->action('Lihat Detail Tim', $this->details['action_url'])
            ->line('Terima kasih telah menggunakan AlmamaterConnect!');
    }

    public function toArray($notifiable)
    {
        return [
            'subject' => $this->details['subject'],
            'message' => $this->details['message'],
            'team_id' => $this->details['team_id'] ?? null,
            'type' => $this->details['type'] ?? 'info',
        ];
    }
}