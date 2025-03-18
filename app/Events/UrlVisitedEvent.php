<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UrlVisitedEvent
{
    use Dispatchable, SerializesModels;

    public $urlId;
    public $shortCode;
    public $browser;
    public $location;

    public function __construct($urlId, $shortCode, $browser, $location)
    {
        $this->urlId = $urlId;
        $this->shortCode = $shortCode;
        $this->browser = $browser;
        $this->location = $location;
    }
}
