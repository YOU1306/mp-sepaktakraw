<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\MembershipExpiringNotification;
use App\Services\SmsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-membership-reminders')]
#[Description('Email + SMS + dashboard reminder to members whose billing period expires within 10 days')]
class SendMembershipReminders extends Command
{
    public function handle(): int
    {
        $due = User::query()
            ->role(['user', 'super-user'])
            ->whereNotNull('membership_expires_at')
            ->whereNull('membership_reminder_sent_at')
            ->where('membership_expires_at', '>=', now())
            ->where('membership_expires_at', '<=', now()->addDays(10))
            ->get();

        foreach ($due as $user) {
            $user->notify(new MembershipExpiringNotification);

            if ($user->phone) {
                SmsService::send(
                    $user->phone,
                    "Your MP Sepaktakraw membership ({$user->user_id}) expires on {$user->membership_expires_at->format('d M Y')}. Renew soon to avoid losing access."
                );
            }

            $user->update(['membership_reminder_sent_at' => now()]);
        }

        $this->info("Sent {$due->count()} membership reminder(s).");

        return self::SUCCESS;
    }
}
