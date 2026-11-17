<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * إيميل إعادة تعيين كلمة المرور (SPEC 2 — نسيت كلمة المرور).
 *
 * بديل `Illuminate\Auth\Notifications\ResetPassword` الافتراضي: ده كان
 * يبني الرابط من route اسمها `password.reset` (مش موجودة — الفرونت صفحة
 * `/reset-password` تقرأ `?token=&email=` من الـ query).
 *
 * الشكل المطلوب في SPEC: `{APP_URL}/reset-password?token={token}&email={email}`.
 */
class PasswordResetLinkNotification extends Notification
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
    ) {}

    /**
     * @param  list<string>  $via
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('إعادة تعيين كلمة المرور — '.config('app.name'))
            ->line('استلمنا طلباً لإعادة تعيين كلمة المرور لبريدك الإلكتروني.')
            ->action('إعادة تعيين كلمة المرور', $this->url($notifiable))
            ->line('الرابط صالح لمدة محدودة، وإن لم تطلب هذا الإجراء تجاهل هذه الرسالة.');
    }

    /**
     * رابط الاستعادة بالشكل المتفق عليه في SPEC 2.
     */
    public function url(object $notifiable): string
    {
        $email = urlencode((string) $notifiable->getEmailForPasswordReset());

        return config('app.url').'/reset-password?token='.$this->token.'&email='.$email;
    }
}
