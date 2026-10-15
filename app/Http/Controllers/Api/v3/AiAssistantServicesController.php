<?php

namespace App\Http\Controllers\Api\v3;

use App\Models\CountryCodesModel;
use App\Services\AiAssistant\AppointmentTypeServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Endpoints used only by the AI assistant.
 *
 * appointmentTypeServices: the services the admin panel's new-appointment popup
 * would offer for an appointment type, as data (id, name, ticked, locked). It is
 * READ ONLY - nothing is recorded or deactivated here; the booking call does
 * that, from the ids the patient ticked.
 */
class AiAssistantServicesController extends BaseController
{
    public function appointmentTypeServices(Request $request)
    {
        $input = $request->all();
        $isNew = filter_var($input['is_new_patient'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $errors = [];
        $data = [];
        $status = false;
        $message = __('api.ERR_NOT_FOUND');

        $validator = Validator::make($input, [
            'appointment_type_id' => 'required|integer|min:1',
            'patient_id' => $isNew ? 'nullable|integer|min:1' : 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return self::_sendResult($message, $data, [$validator->errors()], false);
        }

        $services = (new AppointmentTypeServices())->forType(
            (int) $input['appointment_type_id'],
            isset($input['patient_id']) ? (int) $input['patient_id'] : null,
            $isNew,
            $this->birthDate($input['birth_date'] ?? null)
        );

        if ($services === null) {
            return self::_sendResult($message, $data, [['error' => 'appointment type not found']], false);
        }

        // Counts only: no patient detail and no names in the log.
        Log::info('AI assistant services: listed', [
            'appointment_type_id' => (int) $input['appointment_type_id'],
            'new_patient' => $isNew,
            'count' => count($services),
        ]);

        return self::_sendResult(__('api.DATA_FOUND_SUCCESS'), $services, [], true);
    }

    /**
     * The practice's active country codes, for the assistant's "which country is
     * your number from?" question. READ ONLY, and public information: it needs
     * the app key, not a patient's token, because a new patient has none yet.
     */
    public function countryCodes(Request $request)
    {
        $rows = CountryCodesModel::where('is_active', 1)->orderBy('id')->get(['iso_code', 'country_name_en', 'country_name_de', 'phone_code']);

        $data = $rows->map(fn ($row) => [
            'iso' => (string) $row->iso_code,
            'name_en' => (string) $row->country_name_en,
            'name_de' => (string) $row->country_name_de,
            'code' => (string) $row->phone_code,
        ])->values()->all();

        return self::_sendResult(__('api.DATA_FOUND_SUCCESS'), $data, [], true);
    }

    /** Y-m-d, d-m-Y or d.m.Y in; Y-m-d (or null) out. */
    private function birthDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if (preg_match('/^(\d{2})[-.](\d{2})[-.](\d{4})$/', $value, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        return $value === '' ? null : $value;
    }
}
