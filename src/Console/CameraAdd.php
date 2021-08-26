<?php

namespace TromsFylkestrafikk\Camera\Console;

use TromsFylkestrafikk\Camera\Models\Camera;
use Illuminate\Console\Command;

class CameraAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'camera:add
                           { --N|name= : Camera name (*) }
                           { --i|id= : Camera ID }
                           { --d|model= : Camera model }
                           { --m|mac= : MAC address of camera }
                           { --p|ip= : IP address of camera }
                           { --a|latitude= : Latitude of camera (*) }
                           { --o|longitude= : Longitude of station (*) }
                           { --f|force : Force add even if exists. Missing fields becomes NULL. }';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add camera used on our map';

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
        $this->info("Adding new camera");
        $camera = $this->aquireCameraInfo();
        $camera->save();
        $this->info(sprintf("New camera added with ID: %d", $camera->id));
        return 0;
    }

    protected function aquireCameraInfo(): Camera
    {
        $camera = new Camera();
        $confirmed = false;
        while (!$confirmed) {
            $camera->fill([
                'camera_id' => $this->cliOrAsk('id', "Unique identifier from camera"),
                'name' => $this->cliOrAsk('name', "Name your camera", true),
                'model' => $this->cliOrAsk('model', "Camera model"),
                'mac' => $this->cliOrAsk('mac', "Camera MAC address"),
                'ip' => $this->cliOrAsk('ip', "Camera IP address"),
                'latitude' => floatval($this->cliOrAsk('latitude', "Latitude", true)),
                'longitude' => floatval($this->cliOrAsk('longitude', "Longitude", true)),
            ]);
            if ($this->option('force')) {
                break;
            }
            $confirmed = $this->confirm(sprintf(
                "Creating a new camera with the following params:\n\t Name: %s\n\t Model: %s\n\t MAC: %s\n\t IP: %s\n\t Lat/Lng: [%2.5f, %2.5f]\n\n Is this correct?",
                $camera->name,
                $camera->model,
                $camera->mac,
                $camera->ip,
                $camera->latitude,
                $camera->longitude
            ), true);
            if ($confirmed) {
                $confirmed = !$this->abortCreateIfExists($camera);
            }
        }
        return $camera;
    }

    protected function abortCreateIfExists(Camera $camera)
    {
        return array_reduce(['camera_id', 'name', 'mac'], function ($carry, $property) use ($camera) {
            if ($carry) {
                return $carry;
            }
            if ($camera->{$property} === null) {
                return $carry;
            }
            $existing = Camera::where($property, $camera->{$property})->first();
            return $existing ? (!$this->confirm(
                sprintf(
                    "An existing camera with %s = %s already exists.\n Really create new? (Abort with ^C if provided as argument)",
                    $property,
                    $camera->{$property},
                ),
                false
            )) : false;
        }, false);
    }

    protected function cliOrAsk($param, $question, $required = false)
    {
        if ($this->option($param)) {
            return $this->option($param);
        }
        $force = $this->option('force');
        while ($required) {
            if ($force) {
                throw new Exception("Missing required values when using --force");
            }
            $value = $this->ask($question . ' *');
            if ($value) {
                return $value;
            }
        }
        if ($force) {
            return null;
        }
        return $this->ask($question);
    }
}
