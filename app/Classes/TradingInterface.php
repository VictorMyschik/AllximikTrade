<?php

namespace App\Classes;

interface TradingInterface
{
  public function getOrderBook(): array;
}