<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * إيميل تفعيل البريد — بديل `VerifyEmail` الافتراضي (SPEC 2 + مهمة ClickUp #12).
 *
 * الفرق عن الافتراضي: الرابط موقّع (signed) على مسار الـ API
 * `GET /api/v1/auth/email/verify/{id}/{hash}` ومش مسار واجهة — والفحص في
 * `EmailVerificationController::verify`. الـ hash = sha1(email) (نفس توقيع
 * Supabase) والتوقيع بيغطي `{id, hash}` فيمنع أي تعديل.
 *
 * `url()` static حتى تُبنى الرابط في الاختبارات بلا إرسال فعلي (Notification::fake).
 */
class VerifyEmailNotification extends Notification
{
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
            ->subject('تفعيل بريدك الإلكتروني — '.config('app.name'))
            ->line('أهلاً '.(string) ($notifiable->name ?? ''))
            ->line('شكراً لتسجيلك في '.config('app.name').'. لتفعيل حسابك اضغط على الزر بالأسفل.')
            ->action('تفعيل البريد الإلكتروني', self::url($notifiable))
            ->line('الرابط صالح لمدة محدودة، وإن لم تطلب تسجيل الدخول تجاهل هذه الرسالة.');
    }

    /**
     * الرابط الموقّع لتفعيل البريد (SPEC 2: `auth.verify-email`).
     */
    public static function url(object $notifiable): string
    {
        return URL::signedRoute('auth.verify-email', [
            'id' => $notifiable->getKey(),
            'hash' => sha1((string) $notifiable->email),
        ]);
    }
}
