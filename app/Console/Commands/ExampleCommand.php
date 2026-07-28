<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
// use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
// use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;

class ExampleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'example:command {--website_id=}';
    protected $signature = 'example:command {--tenant_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Example Command - Tenant Aware';

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
    public function __construct()
    {
        parent::__construct();
        // $this->websites = app(WebsiteRepository::class);
        // $this->connection = app(Connection::class);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $website_id = $this->option('website_id');
        // try{
        //     $website = $this->websites->query()->where('id', $website_id)->firstOrFail();
        //     $this->connection->set($website);
        //     $this->info('Running Command on website_id: ' . $website_id);

        //     $this->tenantHandle();

        //     $this->connection->purge();
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
        try{
            $tenant = \App\Models\Tenant::find($tenant_id);
            $this->info('Running Command on tenant_id: ' . $tenant_id);
            $this->tenantHandle();
        } catch (ModelNotFoundException $e) {
            throw new RuntimeException(
                sprintf(
                    'The tenancy tenant_id=%d does not exist.',
                    $tenant_id
                )
            );
        }
    }

    /**
     * Execute the console command. Tenant Aware.
     *
     * @return mixed
     */
    public function tenantHandle()
    {
        // You are now Tenant Aware
        // Execute something, dispatch a Job, or anything else
    }
}