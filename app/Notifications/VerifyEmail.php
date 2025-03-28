<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as LaravelVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends LaravelVerifyEmail
{
    /**
     * Get the verify email notification mail message for the given URL.
     *
     * @param  string  $url
     */
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify Email Address')
            ->greeting('Halo !')
            ->line('Silakan klik tombol dibawah ini untuk memverifikasi akun Anda.')
            ->action('Verifikasi Alamat Email', $url)
            ->line('Abaikan email ini jika Anda merasa tidak membuat akun di website Toko Jogja Electrik.');
    }
}
