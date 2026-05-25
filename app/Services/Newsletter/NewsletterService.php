<?php

namespace App\Services\Newsletter;

use App\Enums\NewsletterStatusEnum;
use App\Mail\NewsletterVerification;
use App\Models\Newsletter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NewsletterService
{
    public function subscribe(string $email, ?string $name = null): Newsletter
    {
        $newsletter = Newsletter::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'token' => Str::random(64),
                'status' => NewsletterStatusEnum::Pending,
            ]
        );
        Mail::to($email)->send(new NewsletterVerification($newsletter));
        return $newsletter;
    }

    public function verify(string $token): bool
    {
        $newsletter = Newsletter::where('token', $token)->first();
        if (!$newsletter) {
            return false;
        }
        $newsletter->update([
            'status' => NewsletterStatusEnum::Verified,
            'verified_at' => now(),
        ]);
        return true;
    }

    public function unsubscribe(string $token): bool
    {
        $newsletter = Newsletter::where('token', $token)->first();
        if (!$newsletter) {
            return false;
        }
        $newsletter->update(['status' => NewsletterStatusEnum::Unsubscribed]);
        return true;
    }
}
