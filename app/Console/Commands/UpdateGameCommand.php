<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\UpdateGameJob;
use App\Models\Def\MainGamePlat;


class UpdateGameCommand extends Command {
  
    protected $signature          = 'updateGame';

    protected $description        = 'updateGame';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

      $existMainGamePlat = MainGamePlat::first();
      if($existMainGamePlat){
            dispatch(new UpdateGameJob());
      }
    }
}