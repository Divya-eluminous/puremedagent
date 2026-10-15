<?php

namespace App\Services\AiAssistant;

use App\Models\AppointmentTypeHasExaminationsModel;
use App\Models\AppointmentTypeHasNonExaminationsModel;
use App\Models\AppointmentTypesModel;
use App\Models\ChannelsRemindersSettingModel;
use App\Models\PatientsHasServiceReminderModel;
use App\Models\PatientsModel;
use Illuminate\Support\Facades\DB;

/**
 * The services the admin panel's "new appointment" popup offers for an
 * appointment type, as data - for the AI assistant.
 *
 * READ ONLY. It selects and never writes: no reminder is deactivated here (that
 * happens when the appointment is booked, from the ids the patient ticked), and
 * unlike the app's getPatientSelectedExaminations nothing is recorded as
 * "displayed".
 *
 * It follows Admin\AppointmentController::getServices() (an existing patient)
 * and, for a new patient, getServices() with no patient followed by
 * getExtraServices() (age-based services from the birth date), rule for rule -
 * including the order, which services start ticked and which are locked.
 * services_endpoint_test.php compares it with the admin functions for every
 * appointment type and patient in the test tenant, so a change to either side
 * that makes them drift apart fails a test.
 *
 * Only names and flags leave this class: no description, url or document.
 */
class AppointmentTypeServices
{
    /**
     * @param  int          $typeId       the appointment type
     * @param  int|null     $patientId    the patient; ignored for a new patient
     * @param  bool         $isNewPatient true for someone just registered: no reminder history
     *                                    is read, and age-based extra services are added
     * @param  string|null  $birthDate    Y-m-d, used only for a new patient
     * @return array<int, array{id: int, name: string, checked: bool, locked: bool}>|null
     *         null when the appointment type does not exist
     */
    public function forType(int $typeId, ?int $patientId, bool $isNewPatient = false, ?string $birthDate = null): ?array
    {
        $type = $typeId > 0 ? AppointmentTypesModel::withTrashed()->find($typeId) : null;

        if (!$type) {
            return null;
        }

        // The admin passes an empty patient for a new one, so no reminder row can match.
        $patient = $isNewPatient ? 0 : max(0, (int) $patientId);
        $today = date('Y-m-d');

        $rows = $this->typeAndReminderServices($type, $patient, $today);
        $nonServices = AppointmentTypeHasNonExaminationsModel::getAppointmentNonServcies($typeId);
        $out = [];

        foreach ($rows as $row) {
            if (in_array($row['id'], $nonServices, true)) {
                continue;
            }

            $item = $this->display($row);

            if ($item) {
                $out[] = $item;
            }
        }

        if ($isNewPatient) {
            foreach ($this->extraServices($type, $birthDate, $nonServices) as $extra) {
                $out[] = $extra;
            }
        }

        return $this->unique($out);
    }

    /**
     * getServices(): the type's own services (filtered by the patient's
     * reminders) followed by services the patient has reminders due for.
     *
     * @return array<int, array<string, mixed>>
     */
    private function typeAndReminderServices(AppointmentTypesModel $type, int $patient, string $today): array
    {
        $own = AppointmentTypeHasExaminationsModel::where('appoinment_type_has_examinations.appoinment_id', $type->id)
            ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')
            ->get(['examinations.id', 'examinations.name', 'examinations.description', 'examinations.show_as_recommended']);

        $own = $own->filter(function ($item) use ($type, $patient, $today) {
            if ($item->name == $type->name) {
                return true;
            }

            $due = $this->latestReminders($patient)
                ->where('examinations.id', $item->id)
                ->whereRaw("date(reminder_date) <= '" . $today . "'")
                ->groupBy('patient_has_service_reminder.service_id')
                ->get();

            // A service with a reminder due is not offered here (the reminder
            // list below carries it instead).
            if ($due->count() > 0) {
                return false;
            }

            // Any reminder at all for this service, due or not, also hides it.
            return PatientsHasServiceReminderModel::where('patient_id', $patient)->where('service_id', $item->id)->first() === null;
        });

        $ownIds = $own->pluck('id')->unique()->all();

        $reminders = PatientsHasServiceReminderModel::select(DB::raw('examinations.id,examinations.name,examinations.description,reminder_status,patient_has_service_reminder.id as reminderid'))
            ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
            ->join(
                DB::raw("(SELECT service_id,patient_has_service_reminder.id as reminderid,MAX(appointment_id) appointment_id
                    FROM patient_has_service_reminder
                    WHERE patient_id='" . $patient . "' and reminder_status IN('ignore','Set')
                    and status='activate'
                    AND (
                    ( (deleted_at IS NULL AND cycle_no = 1 AND date(reminder_date) <= '" . $today . "' AND type!='age' )
                       OR
                       (  deleted_at IS NULL and cycle_no>=0 AND date(reminder_date) <= '" . $today . "' and type='age'
                       )
                    )
                    OR
                    ( (deleted_at IS NULL AND cycle_no > 1 AND date(reminder_date) >= '" . $today . "' AND type!='age')
                       OR (
                          (deleted_at IS  NULL AND cycle_no > 1 AND date(reminder_date) >= '" . $today . "' and type='age' and appointment_id!=0)
                          OR (deleted_at IS  NULL AND cycle_no >= 1 AND date(reminder_date) >= '" . $today . "' and type='age' and appointment_id=0))
                    )
                )  GROUP BY service_id)
                patientremidners"),
                function ($join) {
                    $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                    $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                }
            )
            ->where('patient_has_service_reminder.patient_id', $patient)
            ->where('patient_has_service_reminder.status', 'activate')
            ->whereRaw("examinations.show_as_reminder='1'")
            ->whereNotIn('examinations.id', $ownIds)
            ->groupBy('patient_has_service_reminder.service_id')
            ->get();

        $reminders = $reminders->filter(fn ($item) => $this->reminderServiceApplies($item, $patient, $today));

        $merged = [];

        foreach ($own as $item) {
            $merged[(int) $item->id] = $this->row($item, $type);
        }

        foreach ($reminders as $item) {
            $merged[(int) $item->id] = $this->row($item, $type);
        }

        return array_values($merged);
    }

    /** The patient's most recent active Set/ignore reminder per service, joined to its examination. */
    private function latestReminders(int $patient)
    {
        return PatientsHasServiceReminderModel::select(DB::raw('examinations.id,examinations.name,examinations.description,reminder_status'))
            ->join('examinations', 'examinations.id', 'patient_has_service_reminder.service_id')
            ->join(
                DB::raw("(SELECT service_id,MAX(appointment_id) appointment_id
                            FROM patient_has_service_reminder
                            WHERE patient_id='" . $patient . "' and reminder_status IN('ignore','Set')
                            and status='activate'
                            and deleted_at is NULL GROUP BY service_id)
                        patientremidners"),
                function ($join) {
                    $join->on('patient_has_service_reminder.service_id', '=', 'patientremidners.service_id');
                    $join->on('patient_has_service_reminder.appointment_id', '=', 'patientremidners.appointment_id');
                }
            )
            ->where('patient_has_service_reminder.patient_id', $patient)
            ->where('patient_has_service_reminder.status', 'activate');
    }

    /** The admin's filter on services that come from a reminder (age, general and check-up reminders). */
    private function reminderServiceApplies($item, int $patient, string $today): bool
    {
        $age = ChannelsRemindersSettingModel::where('service_id', $item->id)->where('activated_reminder', 'age')->first();

        if (!empty($age)) {
            $person = PatientsModel::find($patient);

            if (!empty($person)) {
                $years = $person->age;

                if ($age->age_from <= $years && $age->age_to >= $years) {
                    if ($item->reminder_status == 'executed') {
                        if (empty($this->upcomingSetReminder($item->id, $patient, $today))) {
                            return true;
                        }
                    } else {
                        return true;
                    }
                }
            }
        }

        foreach (['general', 'checkup'] as $kind) {
            $setting = ChannelsRemindersSettingModel::where('service_id', $item->id)->where('activated_reminder', $kind)->first();

            if (!empty($setting)) {
                if ($item->reminder_status == 'executed') {
                    if (empty($this->upcomingSetReminder($item->id, $patient, $today))) {
                        return true;
                    }
                } else {
                    return true;
                }
            }
        }

        return false;
    }

    private function upcomingSetReminder(int $serviceId, int $patient, string $today)
    {
        return PatientsHasServiceReminderModel::where('service_id', $serviceId)
            ->where('patient_id', $patient)
            ->where('reminder_status', 'Set')
            ->whereRaw("date(reminder_date) >= '" . $today . "'")
            ->first();
    }

    /**
     * The admin's "map" step: which services start ticked, and which is the one
     * named like the appointment type.
     *
     * @return array<string, mixed>
     */
    private function row($item, AppointmentTypesModel $type): array
    {
        $row = [
            'id' => (int) $item->id,
            'name' => (string) $item->name,
            'description' => $item->description,
            // Services that come from a reminder do not carry this column in the admin either.
            'show_as_recommended' => $item->show_as_recommended ?? null,
            'checked' => false,
            'same_name' => false,
        ];

        if ($item->name == $type->name) {
            $row['checked'] = true;
            $row['same_name'] = true;
        } elseif (empty($item->description)) {
            $row['checked'] = true;
        }

        return $row;
    }

    /**
     * The admin's checkbox rules for a new appointment.
     *
     * @param  array<string, mixed>  $row
     * @return array{id: int, name: string, checked: bool, locked: bool}|null
     */
    private function display(array $row): ?array
    {
        $recommended = (string) ($row['show_as_recommended'] ?? '') === '1';

        if ($row['checked'] && !$recommended) {
            return $this->item($row, true, $row['same_name']);
        }

        if ($recommended || (string) ($row['description'] ?? '') !== '') {
            return $this->item($row, $row['same_name'], $row['same_name']);
        }

        return null;
    }

    /**
     * getExtraServices(): age-based services a new patient is offered, from the
     * birth date alone.
     *
     * @param  array<int, int>  $nonServices
     * @return array<int, array{id: int, name: string, checked: bool, locked: bool}>
     */
    private function extraServices(AppointmentTypesModel $type, ?string $birthDate, array $nonServices): array
    {
        // The admin looks the type up without withTrashed() here, so for a removed
        // type its extra services come back empty.
        if ($type->trashed()) {
            return [];
        }

        $birthDate = $this->cleanDate($birthDate);
        $ownIds = AppointmentTypeHasExaminationsModel::where('appoinment_type_has_examinations.appoinment_id', $type->id)
            ->join('examinations', 'examinations.id', 'appoinment_type_has_examinations.examination_id')
            ->get(['examinations.id'])->pluck('id')->unique()->all();

        $candidates = ChannelsRemindersSettingModel::join('examinations', 'examinations.id', 'preferred_channels_for_reminders_setting.service_id')
            ->where('preferred_channels_for_reminders_setting.type', 'service')
            ->where('preferred_channels_for_reminders_setting.activated_reminder', 'age')
            ->where('examinations.show_as_reminder', '1')
            ->whereNull('examinations.deleted_at')
            ->whereNotIn('examinations.id', $ownIds)
            ->get(['examinations.id', 'examinations.name', 'examinations.description']);

        $out = [];

        foreach ($candidates as $item) {
            $age = ChannelsRemindersSettingModel::where('service_id', $item->id)->where('activated_reminder', 'age')->first();

            if (!empty($age)) {
                if ($birthDate === null) {
                    continue;
                }

                $years = (int) date('Y') - (int) date('Y', strtotime($birthDate));

                if (!($age->age_from <= $years && $age->age_to >= $years)) {
                    continue;
                }
            }

            if (in_array((int) $item->id, $nonServices, true)) {
                continue;
            }

            $ticked = $item->name == $type->name || empty($item->description);
            $out[] = $this->item(['id' => (int) $item->id, 'name' => (string) $item->name], $ticked, false);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{id: int, name: string, checked: bool, locked: bool}
     */
    private function item(array $row, bool $checked, bool $locked): array
    {
        return ['id' => (int) $row['id'], 'name' => (string) $row['name'], 'checked' => $checked, 'locked' => $locked];
    }

    /**
     * @param  array<int, array{id: int, name: string, checked: bool, locked: bool}>  $items
     * @return array<int, array{id: int, name: string, checked: bool, locked: bool}>
     */
    private function unique(array $items): array
    {
        $seen = [];
        $out = [];

        foreach ($items as $item) {
            if (!isset($seen[$item['id']])) {
                $seen[$item['id']] = true;
                $out[] = $item;
            }
        }

        return $out;
    }

    /** A real Y-m-d date, or null. An unreadable date must not be read as "1970". */
    private function cleanDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return $value;
    }
}
