<?php

namespace RaziAlsayyed\LaravelCascadedSoftDeletes\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CascadeSoftDeletes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Model
     */
    public $model;

    /**
     * @var string
     */
    public $event;

    /**
     * @var \Carbon\Carbon
     */
    public $instanceDeletedAt;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($model, $event, $instanceDeletedAt)
    {
        $this->model = $model;
        $this->event = $event;
        $this->instanceDeletedAt = $instanceDeletedAt;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->model->cascadeSoftDeletes($this->event, $this->instanceDeletedAt);
    }

}
