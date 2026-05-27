<?php

namespace App\Services\Newsletter;

use App\Mail\NewsletterVerification;
use App\Models\Newsletter;
use App\Models\Platform;
use Illuminate\Support\Facades\Mail;

class NewsletterService
{
    public function subscribe(string $email, ?string $name = null): Newsletter
    {
        // Resolve platform_id
        $platformId = request('platform_id');
        if (! $platformId) {
            $platformId = Platform::where('domain', request()->getHost())->first()?->id
                ?? (Platform::first()?->id ?? 1);
        }

        $result = Newsletter::createOrUpdate([
            'email' => $email,
            'name' => $name,
            'platform_id' => $platformId,
            'is_verified' => false,
            'status' => Newsletter::STATUS_INACTIVE,
            'subscription_source' => Newsletter::SOURCE_API,
            'language' => request()->header('Accept-Language') ? substr(request()->header('Accept-Language'), 0, 2) : 'es',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $newsletter = $result['newsletter'];

        // Always ensure tokens exist
        if (empty($newsletter->verification_token)) {
            $newsletter->regenerateVerificationToken();
        }

        Mail::to($email)->send(new NewsletterVerification($newsletter));

        return $newsletter;
    }

    public function verify(string $token): bool
    {
        $newsletter = Newsletter::findByVerificationToken($token);
        if (! $newsletter) {
            return false;
        }

        return $newsletter->verifyEmail();
    }

    public function unsubscribe(string $token): bool
    {
        $newsletter = Newsletter::findByUnsubscribeToken($token);
        if (! $newsletter) {
            return false;
        }

        return $newsletter->unsubscribe();
    }
}
