<?php

namespace App\Http\Controllers;

use App\Events\UrlVisitedEvent;
use App\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;

use VladimirYuldashev\LaravelQueueRabbitMQ\Facades\RabbitMQ;

class UrlController extends Controller
{
    public function index(Request $request)
    {
        $urls = Url::all();

        return response()->json(
            [ "data" => $urls ]);
    }

    public function store(Request $request)
    {
        $request->validate(['original_url' => 'required|url']);

        $url = Url::create([
            'user_id' => auth()->id(),
            'original_url' => $request->original_url,
            'short_code' => Str::random(6)
        ]);

        return response()->json($url);
    }

    public function redirect(Request $request, $shortCode)
    {
        $agent = new Agent();
        $browser = $agent->browser();
        $ip = $request->ip();
        $locationData = Location::get($ip);
        $location = $locationData ? $locationData->countryName : 'Unknown';
        $url = Url::where('short_code', $shortCode)->firstOrFail();

        event(new UrlVisitedEvent($url->id, $url->shortened_code, $browser, $location));
        
        return redirect($url->original_url);
    }
}