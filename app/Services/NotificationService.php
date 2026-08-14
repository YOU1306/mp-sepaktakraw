<?php

namespace App\Services;

use App\Models\RegistrationApplication;
use App\Models\User;
use App\Notifications\NewDistrictApplicationNotification;

class NotificationService
{
    /**
     * When an individual/official/coach/manager/referee registration is paid
     * for, the district federation (Super User for that district) holds the
     * review responsibility and should be notified immediately.
     */
    public static function notifyDistrictOfPayment(RegistrationApplication $application): void
    {
        if ($application->type !== RegistrationApplication::TYPE_INDIVIDUAL || ! $application->district_id) {
            return;
        }

        $districtFederations = User::query()
            ->role('super-user')
            ->where('district_id', $application->district_id)
            ->where('status', User::STATUS_ACTIVE)
            ->get();

        foreach ($districtFederations as $federation) {
            $federation->notify(new NewDistrictApplicationNotification($application));
        }
    }
}
