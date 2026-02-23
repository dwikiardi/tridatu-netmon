<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    protected $baseUrl;
    protected $session;
    protected $delay;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('WAHA_BASE_URL', 'http://localhost:3000'), '/');
        $this->session = env('WAHA_SESSION', 'default');
        $this->apiKey = env('WAHA_API_KEY');
        $this->delay = (int) env('WAHA_DELAY', 2);
    }

    public function sendMessage($to, $text)
    {
        // Add delay to prevent ban as requested
        if ($this->delay > 0) {
            sleep($this->delay);
        }

        try {
            $apiKey = trim($this->apiKey);
            $url = "{$this->baseUrl}/api/sendText";

            $payload = [
                'chatId' => $this->formatNumber($to),
                'text' => $text,
                'session' => $this->session,
            ];

            // Chain properly: withHeaders() returns new instance, must be chained
            $response = Http::asJson()
                ->withHeaders(['X-Api-Key' => $apiKey])
                ->post($url, $payload);

            if ($response->failed()) {
                Log::error("WAHA Send Error: " . $response->status() . " " . $response->reason());
                Log::error("Body: " . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("WAHA Exception: " . $e->getMessage());
            return false;
        }
    }

    protected function formatNumber($number)
    {
        // Ensure number ends with @c.us or @g.us
        if (strpos($number, '@') === false) {
            // Remove any non-numeric characters
            $number = preg_replace('/[^0-9]/', '', $number);
            
            // Convert 08... to 628...
            if (strpos($number, '0') === 0) {
                $number = '62' . substr($number, 1);
            }
            
            $number .= '@c.us';
        }
        return $number;
    }
}
