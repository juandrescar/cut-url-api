<?php

namespace App\Listeners;

use App\Events\UrlVisitedEvent;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class UrlVisitedListener
{
    public function handle(UrlVisitedEvent $event)
    {
        $data = json_encode([
            'pattern' => 'urls_statistics',
            'data' =>[
                'url_id' => $event->urlId,
                'browser' => $event->browser,
                'location' => $event->location,
            ]
        ]);
        
        Log::info('Mensaje enviado a RabbitMQ: ' . $data);
        
        Queue::connection('rabbitmq')->pushRaw($data, 'urls_statistics');
    }
}