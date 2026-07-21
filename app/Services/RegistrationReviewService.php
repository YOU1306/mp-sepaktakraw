<?php

namespace App\Services;

use App\Mail\RegistrationApprovedMail;
use App\Mail\RegistrationRejectedMail;
use App\Models\RegistrationApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RegistrationReviewService
{
    /**
     * Map an application type to the login role + user_id prefix type.
     *
     * @return array{role: string, type: string}
     */
    protected static function roleFor(string $applicationType): array
    {
        return match ($applicationType) {
            RegistrationApplication::TYPE_INDIVIDUAL => ['role' => 'user', 'type' => 'user'],
            RegistrationApplication::TYPE_FEDERATION => ['role' => 'super-user', 'type' => 'super-user'],
            RegistrationApplication::TYPE_CLUB => ['role' => 'club', 'type' => 'club'],
            default => ['role' => 'user', 'type' => 'user'],
        };
    }

    public static function approve(RegistrationApplication $application, User $reviewer): array
    {
        return DB::transaction(function () use ($application, $reviewer) {
            $map = self::roleFor($application->type);

            $credentials = CredentialService::createAccount(
                role: $map['role'],
                attributes: [
                    'name' => $application->applicant_name,
                    'email' => $application->applicant_email,
                    'district_id' => $application->district_id,
                    'email_verified_at' => now(),
                ],
                type: $map['type'],
            );

            /** @var User $account */
            $account = $credentials['user'];

            $application->update([
                'status' => RegistrationApplication::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'user_id' => $account->id,
            ]);

            if ($application->player) {
                $application->player->update(['user_id' => $account->id]);
            }

            AuditService::logModel('approved', $application, ['created_user' => $account->user_id]);

            if ($application->applicant_email) {
                Mail::to($application->applicant_email)->send(
                    new RegistrationApprovedMail($application, $credentials['user_id'], $credentials['password'])
                );
            }

            return $credentials;
        });
    }

    public static function reject(RegistrationApplication $application, User $reviewer, ?string $reason = null): void
    {
        $application->update([
            'status' => RegistrationApplication::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $reason,
        ]);

        AuditService::logModel('rejected', $application, ['reason' => $reason]);

        if ($application->applicant_email) {
            Mail::to($application->applicant_email)->send(
                new RegistrationRejectedMail($application, $reason)
            );
        }
    }
}
