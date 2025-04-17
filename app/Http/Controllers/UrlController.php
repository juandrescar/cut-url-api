<?php

namespace App\Http\Controllers;

use App\Events\UrlVisitedEvent;
use App\Models\Url;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;
use Symfony\Component\DomCrawler\Crawler;
use VladimirYuldashev\LaravelQueueRabbitMQ\Facades\RabbitMQ;
use Illuminate\Support\Facades\Log;

class UrlController extends Controller
{
    public function index(Request $request)
    {
        $urls = Url::all();

        return response()->json(
            [ "data" => $urls ]);
    }

    public function show(Request $request)
    {
        $url = Url::findOrfail($request->urlId);

        return response()->json(
            [ "data" => $url ]);
    }

    public function store(Request $request)
    {
        $request->validate(['original_url' => 'required|url']);

        $urlPreview = $this->getPreview($request->original_url);

        $url = Url::create(array_merge([
                'user_id' => auth()->id(),
                'original_url' => $request->original_url,
                'short_code' => Str::random(6)
            ],
            $urlPreview
        ));

        return response()->json($url);
    }

    public function redirect(Request $request, $shortCode)
    {
        $agent = new Agent();
        $browser = $agent->browser();
        $ip = $request->ip();

        $locationData = Location::get($ip);
        Log::info($ip. 'IP: ' . $locationData);
        $location = $locationData ? $locationData->countryName : 'Unknown';
        $url = Url::where('short_code', $shortCode)->firstOrFail();

        event(new UrlVisitedEvent($url->id, $url->shortened_code, $browser, $location));
        
        return redirect($url->original_url);
    }

    public function getPreview($url)
    {
        try {
            $client = new \GuzzleHttp\Client([
                'verify' => false,
                'headers' => ['User-Agent' => 'LinkPreviewBot/1.0']
            ]);

            $response = $client->get($url);
            $html = $response->getBody()->getContents();

            $crawler = new Crawler($html);

            // Título
            $title = $crawler->filterXPath("//meta[@property='og:title']")->count()
                ? $crawler->filterXPath("//meta[@property='og:title']")->attr('content')
                : ($crawler->filter('title')->count() ? $crawler->filter('title')->text() : '');

            // Imagen
            $image = $crawler->filterXPath("//meta[@property='og:image']")->count()
                ? $crawler->filterXPath("//meta[@property='og:image']")->attr('content')
                : null;

            // Descripción
            $description = $crawler->filterXPath("//meta[@property='og:description']")->count()
            ? $crawler->filterXPath("//meta[@property='og:description']")->attr('content')
            : (
                $crawler->filterXPath("//meta[@name='description']")->count()
                    ? $crawler->filterXPath("//meta[@name='description']")->attr('content')
                    : null
            );

            // Favicon
            $favicon = null;
            $faviconSelectors = [
                "//link[@rel='icon']",
                "//link[@rel='shortcut icon']",
                "//link[@rel='apple-touch-icon']"
            ];
            foreach ($faviconSelectors as $selector) {
                if ($crawler->filterXPath($selector)->count()) {
                    $favicon = $crawler->filterXPath($selector)->attr('href');
                    break;
                }
            }

            // Normalizar favicon relativo
            if ($favicon && !preg_match('/^https?:\/\//', $favicon)) {
                $parsedUrl = parse_url($url);
                $base = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                $favicon = rtrim($base, '/') . '/' . ltrim($favicon, '/');
            }

            // Dominio
            $parsedUrl = parse_url($url);
            $domain = $parsedUrl['host'] ?? null;

            return [
                'title' => $title,
                'image' => $image,
                'favicon' => $favicon,
                'domain' => $domain,
                'description' => $description
            ];
        } catch (\Exception $e) {
            Log::error('No se pudo obtener la vista previa: ' . $e->getMessage());
            return [];
        }
    }
}