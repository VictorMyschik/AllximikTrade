<?php

namespace App\Jobs;

use App\Http\Controllers\MrTestController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExmoJobTrading implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $input;

  public function __construct(array $input)
  {
    $this->input = $input;
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    MrTestController::trading($this->input);
  }
}
