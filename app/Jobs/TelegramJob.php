<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;
use App\Lib\Telegram;

class TelegramJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data = null;

    public function __construct($data) {
        $this->data = $data;
    }

    public function handle()
    {
        if(isset($this->data['type'])) {
            return Telegram::$this->data['type']($this->data['chat_id'], $this->data['message']); 
        } else {
            return Telegram::sendCode($this->data['text'], $this->data['carrier_id']); 
        }
        
    }
}
