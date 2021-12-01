<?php

namespace App\Jobs;

use App\Http\Controllers\MrTestController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TradingJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected array $input;

  public function __construct(array $input)
  {
    $this->input = $input;
    $this->connection = 'redis';
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    MrTestController::tradingByStock($this->input);
  }
}
