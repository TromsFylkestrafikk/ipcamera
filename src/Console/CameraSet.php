<?php

namespace TromsFylkestrafikk\Camera\Console;

use TromsFylkestrafikk\Camera\Models\Camera;
use Illuminate\Console\Command;

class CameraSet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'camera:set
                            { id : Camera (Laravel) ID }
                            { property : Property to set }
                            { value : New value }
                            { --f|force : Do not ask for confirmation }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set property of camera';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument('id');
        $camera = Camera::find($id);
        if (!$camera) {
            $this->error("Camera not found");
            return 1;
        }
        $allowed = [
            'camera_id',
            'name',
            'model',
            'ip',
            'mac',
            'latitude',
            'longitude',
        ];
        $property = $this->argument('property');
        $value = $this->argument('value');
        if (!in_array($property, $allowed)) {
            $this->warn(sprintf(
                "Property doesn't exist: %s\nAllowed properties: %s",
                $property,
                implode(', ', $allowed)
            ));
            return 1;
        }
        if (
            $this->option('force') || $this->confirm(sprintf(
                "Really set property '%s' to new value '%s'?",
                $property,
                $value
            ), false)
        ) {
            $camera->{$property} = $value;
            $camera->save();
        }
        return 0;
    }
}
