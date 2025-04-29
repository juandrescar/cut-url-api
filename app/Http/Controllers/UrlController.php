<?php

namespace App\Http\Controllers;

use App\Events\UrlVisitedEvent;
use App\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;
use Symfony\Component\DomCrawler\Crawler;

class UrlController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return response()->json(
            [ "data" => $user->urls ]);
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $url = Url::where("user_id", $user->id)->findOrfail($request->urlId);

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

            return [
                'title' => $this->getMetaContent($crawler, [
                    "//meta[@property='og:title']",
                ]) ?: ($crawler->filter('title')->count() ? $crawler->filter('title')->text() : ''),
                'image' => $this->getMetaContent($crawler, "//meta[@property='og:image']", null),
                'favicon' => $this->getFavicon($crawler, $url),
                'domain' => $this->getDomain($url),
                'description' => $this->getMetaContent($crawler, [
                    "//meta[@property='og:description']",
                    "//meta[@name='description']",
                ], null)
            ];
        } catch (\Exception $e) {
            Log::error('No se pudo obtener la vista previa: ' . $e->getMessage());
            return [];
        }
    }

    private function getMetaContent($crawler, $xpaths, $fallback = '')
    {
        foreach ((array) $xpaths as $xpath) {
            $elements = $crawler->filterXPath($xpath);
            if ($elements->count()) {
                return $elements->attr('content');
            }
        }
        return $fallback;
    }

    private function getFavicon($crawler, $url)
    {
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
        return $favicon;
    }

    private function getDomain($url)
    {
        $parsedUrl = parse_url($url);
        return $parsedUrl['host'] ?? null;
    }
}
