<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// Hyn Tenancy imports (commented out)
// use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
// use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\AppointmentModel;
use App\Models\AppointmentHasQueueNumberModel;
use Illuminate\Support\Facades\Log;

// use Carbon;
use Carbon\Carbon;
use DB;
use Stancl\Tenancy\Facades\Tenancy;

class MigrateAppointmentData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Hyn Tenancy signature (commented out)
    // protected $signature = 'MigrateAppointmentData:migrate {--website_id=}';
    
    // Stancl Tenancy signature
    protected $signature = 'MigrateAppointmentData:migrate {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'migrate appointment data to event table';

    // Hyn Tenancy properties (commented out)
    // /**
    //  * @var Connection
    //  */
    // private $connection;

    // /**
    //  * @var WebsiteRepository
    //  */
    // private $websites;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        AppointmentModel $AppointmentModel,
        AppointmentHasQueueNumberModel $AppointmentHasQueueNumberModel
    ) {
        parent::__construct();
        // Hyn Tenancy initialization (commented out)
        // $this->websites = app(WebsiteRepository::class);
        // $this->connection = app(Connection::class);
        $this->BaseModel = $AppointmentModel;
        $this->AppointmentHasQueueNumberModel = $AppointmentHasQueueNumberModel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        log::info("MigrateAppointmentData handle function start");
        
        // Hyn Tenancy (commented out)
        // $website_id = $this->option('website_id');
        // try {
        //     if (!empty($website_id) && $website_id != '0') {
        //         $website = $this->websites->query()->where('id', $website_id)->firstOrFail();
        //         $this->connection->set($website);
        //         self::tenantHandle($website_id);
        //         $this->connection->purge();
        //     }
        // } catch (ModelNotFoundException $e) {
        //     throw new RuntimeException(
        //         sprintf(
        //             'The tenancy website_id=%d does not exist.',
        //             $website_id
        //         )
        //     );
        // }
        
        // Stancl Tenancy
        $tenant_id = $this->option('tenant_id');
        try {
            if (!empty($tenant_id) && $tenant_id != '0') {
                self::tenantHandle($tenant_id);
                
                // Stancl tenancy cleanup
                tenancy()->end();
            }
        } catch (ModelNotFoundException $e) {
            throw new RuntimeException(
                sprintf(
                    'The tenancy tenant_id=%d does not exist.',
                    $tenant_id
                )
            );
        }
        log::info("MigrateAppointmentData handle function end");
    }

    public function tenantHandle($tenant_id)
    //public function tenantHandle($website_id)
    {
        
        // Stancl Tenancy - Get tenant and initialize context
        $tenant = \App\Models\Tenant::find($tenant_id);
        if($tenant) {
            Log::info("Found tenant: " . $tenant->ordination_name);
            tenancy()->initialize($tenant);
            config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
            DB::purge('tenant');
            Log::info("Tenant context initialized for: " . $tenant->ordination_name);
        }

        $collections = DB::connection('tenant')->table('appointment')
            ->select(
                'appointment.id',
                // 'appointment.migration_id',//commented on 02/sept/2025 not used thats why removed
                'appointment.google_event_id',
                'appointment.event_id',
                'appointment.start_date',
                'appointment.end_date',
                'appointment.patient_id',
                'appointment.doctor_id',
                'appointment.appointment_type_id',
                'appointment.notes',
                'appointment.status',
                'appointment.reminder_status',
                'appointment.appointment_status',
                //'appointment.is_app_booked', //commented because not used below on 2-sept-25
                'appointment.assign_to_doc_dashboard',
                'appointment.qrcode_process_status',
                'appointment.created_at',
                'appointment.updated_at',
                'appointment.deleted_at',
                DB::raw("CONCAT(patients.first_name, ' ', patients.family_name) AS patient_name"),
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS doctor_name"),
                'appointment_types.name AS appointment_type_name',
                'patients.email AS patient_email',
                'users.email AS doctor_email',
                'users.google_color_id AS doctor_color_id'
            )
            ->leftJoin('patients', 'appointment.patient_id', '=', 'patients.id')
            ->leftJoin('users', 'appointment.doctor_id', '=', 'users.id')
            ->leftJoin('appointment_types', 'appointment.appointment_type_id', '=', 'appointment_types.id')
            ->whereNull('appointment.event_id')
            ->whereNull('appointment.deleted_at')
            // ->where('appointment.id',2)
            ->get();

        foreach ($collections as $appointment) {
            if (isset($appointment->appointment_type_name) && isset($appointment->doctor_email) && isset($appointment->patient_name)) {
                $event = [
                    'summary' => $appointment->patient_name . ' - ' . $appointment->appointment_type_name,
                    'description' => '<p><strong>Patient:</strong> ' . $appointment->patient_name . '</p>' .
                        '<p><strong>Arzt:</strong> ' . $appointment->doctor_email . '</p>' .
                        '<p><strong>Typ:</strong> ' . $appointment->appointment_type_name . '</p>' .
                        '<p><strong>Beginn:</strong> ' . Carbon::parse($appointment->start_date)->format('F j, Y H:i') . '</p>' .
                        '<p><strong>Ende:</strong> ' . Carbon::parse($appointment->end_date)->format('F j, Y H:i') . '</p>' .
                        '<p><strong>Notizen:</strong> ' . $appointment->notes . '</p>',
                    'color_id' => $appointment->doctor_color_id,
                    'patient_email' => $appointment->patient_email,
                    'patient_name' => $appointment->patient_name,
                    'doctor_email' => $appointment->doctor_email,
                    'start_date_time' => Carbon::parse($appointment->start_date),
                    'end_date_time' => Carbon::parse($appointment->end_date),
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                ];

                $eventId = DB::connection('tenant')->table('events')
                    ->insertGetId($event);

                DB::connection('tenant')->table('appointment')
                    ->where('id', $appointment->id)
                    ->update(['event_id' => $eventId]);
            }
        }

      
    }
}
