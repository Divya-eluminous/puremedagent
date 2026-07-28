<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ReminderNotification;
use App\Console\Commands\AppointmentNotification;
use App\Console\Commands\AppointmentPushNotification;
use App\Console\Commands\ReminderStatus;
use App\Console\Commands\AppointmentStatus;
use App\Console\Commands\UpdatedReminderStatus;
use App\Console\Commands\SelectTimeFrameSlotCommand;
use App\Console\Commands\AgebaseServicesReminders;
use Mail;

use App\Console\Commands\reminderTestCommand; // added on 10-oct-23
use App\Console\Commands\updateServiceRemindersCommand; // added on 23-oct-23
use App\Console\Commands\updateOtherServiceRemindersCommand; // added on 26-oct-23
use App\Console\Commands\QrcodeAppointmentProcessStatus; //added om 4/1/2024 by vijay

use App\Console\Commands\DeleteIgnoreReminders; //added on 6-june-24

use App\Console\Commands\AddDefaultService; //added on 14-aug-24
use App\Console\Commands\MigrateAppointmentData; //added on 16/8/2024 by vijay

use App\Console\Commands\ReminderNotificationNew; //added new on 30-dec-24

use App\Console\Commands\SetReminderNotificationUnSent; //added new on 2-jan-25
use App\Console\Commands\ReminderNotificationFuture; //added new on 5-feb-25

use App\Console\Commands\ReminderPatients; //added new on 30-apr-25
use App\Console\Commands\ReminderCycles; //added new on 30-apr-25
use Log;
use App\Models\Tenant;

use App\Console\Commands\ReminderCyclesNext; //added new on 17-sept-25
use App\Console\Commands\ReminderPatientsNext; //added new on 17-sept-25


use App\Console\Commands\AddServices; //added new on 30-sept-25


class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands enabled for Stancl Tenancy
        Commands\AppointmentPushNotification::class,
        Commands\AppointmentStatus::class,
        Commands\ReminderStatus::class,
        Commands\ReminderNotification::class,
        Commands\AppointmentNotification::class,
        Commands\UpdatedReminderStatus::class,
        Commands\SelectTimeFrameSlotCommand::class,
        Commands\AgebaseServicesReminders::class,
        Commands\reminderTestCommand::class,
        Commands\updateServiceRemindersCommand::class,
        Commands\updateOtherServiceRemindersCommand::class,
        Commands\QrcodeAppointmentProcessStatus::class,
        Commands\DeleteIgnoreReminders::class,
        Commands\AddDefaultService::class,
        Commands\ReminderNotificationNew::class,
        Commands\SetReminderNotificationUnSent::class,
        Commands\ReminderNotificationFuture::class,
        Commands\ReminderPatients::class,
        Commands\ReminderCycles::class,
        Commands\ReminderCyclesNext::class,
        Commands\ReminderPatientsNext::class,
        Commands\AddServices::class,

        
    ];

    protected function scheduleAPNCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); //  set tenant context
                    try {
                        \Artisan::call(AppointmentPushNotification::class, [
                            "--tenant_id" => $tenant->id,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error(
                            "Error running AppointmentPushNotification for Tenant: {$tenant->id}. Message: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    } 
                    // finally {
                    //     tenancy()->end(); //  clean up tenant context
                    // }
                }
            })
            ->everyMinute();
    }

    protected function scheduleAppoitmentStatusCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant);
                    try {
                        \Artisan::call(AppointmentStatus::class, [
                            "--tenant_id" => $tenant->id,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error(
                            "Error in AppointmentStatus for Tenant {$tenant->id}: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    } 
                    // finally {
                    //     tenancy()->end();
                    // }
                }
            })
            ->everyMinute();
    }

    protected function scheduleReminderStatusCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); // set tenant context
                    try {
                        \Artisan::call(ReminderStatus::class, [
                            "--tenant_id" => $tenant->id,
                        ]);

                    } catch (\Throwable $e) {
                        Log::error(
                            "Error running ReminderStatus for Tenant {$tenant->id}. Message: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    } 
                    // finally {
                    //     tenancy()->end(); //  clean up tenant context
                    // }
                }
            })
            ->everyFiveMinutes();
    }

    protected function scheduleUpdateReminderStatusCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); //  set tenant context
                    try {
                        \Artisan::call(UpdatedReminderStatus::class, [
                            "--tenant_id" => $tenant->id,
                        ]);

                    } catch (\Throwable $e) {
                        Log::error(
                            " Error running UpdatedReminderStatus for Tenant {$tenant->id}. Message: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    } 
                    // finally {
                    //     tenancy()->end(); //  clean up tenant context
                    // }
                }
            })
            ->everyMinute();
    }

    protected function scheduleReminderNotificationCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); // set tenant context
                    try {
                        \Artisan::call(ReminderNotification::class, [
                            "--tenant_id" => $tenant->id,
                        ]);

                    } catch (\Throwable $e) {
                        Log::error(
                            " Error running ReminderNotification for Tenant {$tenant->id}. Message: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    }
                    // finally {
                    //     tenancy()->end(); //  clean up tenant context
                    // }
                }
            })
            ->dailyAt("09:00")
            ->withoutOverlapping();
    }

    protected function scheduleAppointmentNotificationCommand(
        Schedule $schedule
    ) {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); // switch tenant DB
                    try {
                        \Artisan::call(AppointmentNotification::class, [
                            "--tenant_id" => $tenant->id,
                        ]);

                    } catch (\Throwable $e) {
                        Log::error(
                            "Error running AppointmentNotification for Tenant {$tenant->id}. Message: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    } 
                    // finally {
                    //     tenancy()->end(); //  always clean up
                    // }
                }
            })
            ->everyMinute();
    }

    protected function scheduleSelectTimeFrameSlotCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); //  switch tenant DB

                    try {
                        \Artisan::call(SelectTimeFrameSlotCommand::class, [
                            "--tenant_id" => $tenant->id,
                        ]);

                    } catch (\Throwable $e) {
                        Log::error(
                            " Error running SelectTimeFrameSlotCommand for Tenant {$tenant->id}. Message: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    } 
                    // finally {
                    //     tenancy()->end(); //  always clean up
                    // }
                }
            })
            ->everyMinute();
    }

    protected function scheduleAgebaseServicesRemindersCommand(
        Schedule $schedule
    ) {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); //  set tenant context

                    try {
                        \Artisan::call(AgebaseServicesReminders::class, [
                            "--tenant_id" => $tenant->id,
                        ]);

                        // Log::info(
                        //     " AgebaseServicesReminders executed for Tenant {$tenant->id}"
                        // );
                    } catch (\Throwable $e) {
                        Log::error(
                            "Error running AgebaseServicesReminders for Tenant {$tenant->id}. Message: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    } 
                    // finally {
                    //     tenancy()->end(); //  clean up
                    // }
                }
            })
            ->daily(); // runs once per day

    }

    protected function scheduleTestRemindersCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); //  set tenant context

                    try {
                        \Artisan::call(reminderTestCommand::class, [
                            "--tenant_id" => $tenant->id,
                        ]);

                       
                    } catch (\Throwable $e) {
                        Log::error(
                            "Error running reminderTestCommand for Tenant {$tenant->id}. Message: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    } 
                    // finally {
                    //     tenancy()->end(); //  clean up
                    // }
                }
            })
            ->daily()
            ->between(
                "09:00",
                "10:00"
                //->timezone('Europe/Berlin')
                //->timezone('Asia/Kolkata')
            );
    }

    //  Updated on 23-Oct-23 for Stancl Tenancy
    protected function scheduleUpdateSRemindersCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); // set tenant context

                    try {
                        \Artisan::call(updateServiceRemindersCommand::class, [
                            "--tenant_id" => $tenant->id,
                        ]);

                    } catch (\Throwable $e) {
                        Log::error(
                            " Error running updateServiceRemindersCommand for Tenant {$tenant->id}. Message: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    }
                    //  finally {
                    //     tenancy()->end(); //  clean up after each tenant
                    // }
                }
            })
            ->everyFiveMinutes();
    }

    //  Updated for Stancl Tenancy
    protected function scheduleUpdateOtherSRemindersCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); //  set tenant context

                    try {
                        \Artisan::call(
                            updateOtherServiceRemindersCommand::class,
                            [
                                "--tenant_id" => $tenant->id,
                            ]
                        );

                        
                    } catch (\Throwable $e) {
                        Log::error(
                            " Error running updateOtherServiceRemindersCommand for Tenant {$tenant->id}. Message: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    } 
                    // finally {
                    //     tenancy()->end(); //  clean up
                    // }
                }
            })
            ->everyFiveMinutes();
    }

    //  Stancl-compatible version
    protected function scheduleQrcodeAppointmentProcessUpdateStatusCommand(
        Schedule $schedule
    ) {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); //  set tenant context

                    try {
                        \Artisan::call(QrcodeAppointmentProcessStatus::class, [
                            "--tenant_id" => $tenant->id,
                        ]);

                    } catch (\Throwable $e) {
                        Log::error(
                            "Error in QrcodeAppointmentProcessStatus for Tenant {$tenant->id}: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    } 
                    // finally {
                    //     tenancy()->end(); //  cleanup tenant context
                    // }
                }
            })
            ->daily(); // runs once per day
    }

    //  Stancl-compatible version
    protected function scheduleDeleteIgnoreReminderStatusCommand(
        Schedule $schedule
    ) {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); //  set tenant context

                    try {
                        \Artisan::call(DeleteIgnoreReminders::class, [
                            "--tenant_id" => $tenant->id,
                        ]);

                    } catch (\Throwable $e) {
                        Log::error(
                            " Error in DeleteIgnoreReminders for Tenant {$tenant->id}: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    } 
                    // finally {
                    //     tenancy()->end(); //  cleanup tenant context
                    // }
                }
            })
            ->dailyAt("02:00"); // run at 2 AM daily
    }

    //  Stancl-compatible version
    protected function scheduleDefaultServiceCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {

                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); //  set tenant context

                    try {
                        \Artisan::call(AddDefaultService::class, [
                            "--tenant_id" => $tenant->id,
                        ]);

                    } catch (\Throwable $e) {
                        Log::error(
                            " Error in AddDefaultService for Tenant {$tenant->id}: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    }
                    // finally {
                    //     tenancy()->end(); //  cleanup tenant context
                    // }
                }
            })
            ->daily(); // runs once per day
    }

    protected function migrateAppointmnetData(Schedule $schedule)
    {
        $schedule
            ->call(function () {

                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); //  set tenant context

                    try {
                        \Artisan::call(MigrateAppointmentData::class, [
                            "--tenant_id" => $tenant->id,
                        ]);

                       
                    } catch (\Throwable $e) {
                        Log::error(
                            " Error in MigrateAppointmentData for Tenant {$tenant->id}: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    } 
                    // finally {
                    //     tenancy()->end(); //  cleanup tenant context
                    // }
                }
            })
            ->daily();
    }

    protected function scheduleReminderNewNotificationCommand(
        Schedule $schedule
    ) {

        $schedule
            ->call(function () {

                // $tenants = \App\Models\Tenant::whereNull('deleted_at')->where('id',2)->get();
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                     //tenancy()->initialize($tenant);

                    try {
                        \Artisan::call(ReminderNotificationNew::class, [
                            "--tenant_id" => $tenant->id,
                        ]);

                        
                    } catch (\Throwable $e) {
                        Log::error(
                            "Error in ReminderNotificationNew for Tenant {$tenant->id}: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    } 
                   /* finally {
                        tenancy()->end();
                    }*/
                }
            })
            ->dailyAt("09:30");
    }

    protected function scheduleReminderUnSentNotificationCommand(
        Schedule $schedule
    ) {
        $schedule
            ->call(function () {
               
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant);

                    try {
                        \Artisan::call(SetReminderNotificationUnSent::class, [
                            "--tenant_id" => $tenant->id,
                        ]);
                        
                    } catch (\Throwable $e) {
                        Log::error(
                            " Error in SetReminderNotificationUnSent for Tenant {$tenant->id}: " .
                                $e->getMessage()
                        );
                    } 
                    // finally {
                    //     tenancy()->end();
                    // }
                }
            })
            ->dailyAt("06:00");
    }

    protected function scheduleReminderFutureNotificationCommand(
        Schedule $schedule
    ) {
        $schedule
            ->call(function () {
               

                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant);

                    try {
                        \Artisan::call(ReminderNotificationFuture::class, [
                            "--tenant_id" => $tenant->id,
                        ]);
                        
                    } catch (\Throwable $e) {
                        Log::error(
                            " Error in ReminderNotificationFuture for Tenant {$tenant->id}: " .
                                $e->getMessage()
                        );
                    }
                    // finally {
                    //     tenancy()->end();
                    // }
                }
            })
            ->dailyAt("23:30");
    }

    protected function scheduleReminderPatientCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant);

                    try {
                        \Artisan::call(ReminderPatients::class, [
                            "--tenant_id" => $tenant->id,
                        ]);
                        Log::info(
                            " ReminderPatients executed for Tenant {$tenant->id}"
                        );
                    } catch (\Throwable $e) {
                        Log::error(
                            " Error in ReminderPatients for Tenant {$tenant->id}: " .
                                $e->getMessage()
                        );
                    }
                    // finally {
                    //     tenancy()->end();
                    // }
                }
            })
           ->everyFiveMinutes();

    }

    protected function scheduleReminderPatientNextCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant);

                    try {
                        \Artisan::call(ReminderPatientsNext::class, [
                            "--tenant_id" => $tenant->id,
                        ]);
                        Log::info(
                            " ReminderPatientsNext executed for Tenant {$tenant->id}"
                        );
                    } catch (\Throwable $e) {
                        Log::error(
                            " Error in ReminderPatientsNext for Tenant {$tenant->id}: " .
                                $e->getMessage()
                        );
                    }
                    // finally {
                    //     tenancy()->end();
                    // }
                }
            })
            ->everyFiveMinutes();
    }

    protected function scheduleReminderCycleCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {
                
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant);

                    try {
                        \Artisan::call(ReminderCycles::class, [
                            "--tenant_id" => $tenant->id,
                        ]);
                       
                    } catch (\Throwable $e) {
                        Log::error(
                            "Error in ReminderCycles for Tenant {$tenant->id}: " .
                                $e->getMessage()
                        );
                    }
                    // finally {
                    //     tenancy()->end();
                    // }
                }
            })
           ->everyFiveMinutes();     

    }

    protected function scheduleReminderCycleNextCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {
                
                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant);

                    try {
                        \Artisan::call(ReminderCyclesNext::class, [
                            "--tenant_id" => $tenant->id,
                        ]);
                       
                    } catch (\Throwable $e) {
                        Log::error(
                            "Error in ReminderCyclesNext for Tenant {$tenant->id}: " .
                                $e->getMessage()
                        );
                    }
                    // finally {
                    //     tenancy()->end();
                    // }
                }
            })
            ->everyFiveMinutes();

    }//

    //Added on 30-sept-25
    protected function scheduleAddServicesCommand(Schedule $schedule)
    {
        $schedule
            ->call(function () {

                $tenants = \App\Models\Tenant::whereNull('deleted_at')->get();
                foreach ($tenants as $tenant) {
                    // tenancy()->initialize($tenant); //  set tenant context

                    try {
                        \Artisan::call(AddServices::class, [
                            "--tenant_id" => $tenant->id,
                        ]);

                    } catch (\Throwable $e) {
                        Log::error(
                            " Error in AddDServices for Tenant {$tenant->id}: " .
                                $e->getMessage(),
                            [
                                "trace" => $e->getTraceAsString(),
                            ]
                        );
                    }
                    // finally {
                    //     tenancy()->end(); //  cleanup tenant context
                    // }
                }
            })
            ->everyFiveMinutes(); // runs once per day 
    }




    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $this->scheduleReminderNewNotificationCommand($schedule); //added new on 30-dec-24

        $this->scheduleAPNCommand($schedule);
        $this->scheduleAppoitmentStatusCommand($schedule);
        $this->scheduleReminderStatusCommand($schedule);
        // $this->scheduleUpdateReminderStatusCommand($schedule);

        //$this->scheduleReminderNotificationCommand($schedule); //live cron for reminder notification commented on 31-dec-24

        // $this->scheduleAppointmentNotificationCommand($schedule);
        $this->scheduleSelectTimeFrameSlotCommand($schedule);
        $this->scheduleAgebaseServicesRemindersCommand($schedule); //commented on 16-feb-24

        // $schedule->command('AppointmentNotification:Notify')->everyMinute();

        // $this->scheduleTestRemindersCommand($schedule); // Added on 10-oct-23

        $this->scheduleUpdateSRemindersCommand($schedule); // Added on 23-oct-23

        // $this->scheduleUpdateOtherSRemindersCommand($schedule); // Added on 26-oct-23

        // $this->scheduleQrcodeAppointmentProcessUpdateStatusCommand($schedule); // Added on 4-1/2024 and commented on 7-may-24
        // $schedule->command('QrcodeAppointmentProcessStatus:update')->daily();

        $this->scheduleDeleteIgnoreReminderStatusCommand($schedule); //added on 6-june-24

        //$this->scheduleDefaultServiceCommand($schedule); //added on 14-aug-24 uncommented on 8-sept-25 commented on 1-oct-25

        $this->migrateAppointmnetData($schedule); // added by vijay 1/8/24





        $this->scheduleReminderUnSentNotificationCommand($schedule); //added new on 2-jan-25

        $this->scheduleReminderFutureNotificationCommand($schedule); //added new on 5-feb-25

        $this->scheduleReminderPatientCommand($schedule); //added new on 30-apr-25
        $this->scheduleReminderCycleCommand($schedule); //added new on 30-apr-25
        $this->scheduleReminderCycleNextCommand($schedule); //added new on 17-sept-25
        $this->scheduleReminderPatientNextCommand($schedule); //added new on 17-sept-25

       // $this->scheduleAddServicesCommand($schedule); //added new on 30-sept-25 //commented on 15-oct-25


    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . "/Commands");

        require base_path("routes/console.php");
    }
}
