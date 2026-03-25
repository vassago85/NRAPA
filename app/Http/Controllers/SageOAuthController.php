<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\SageNetworkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SageOAuthController extends Controller
{
    /**
     * Redirect the owner to Sage's Azure B2C authorization page.
     */
    public function redirect(Request $request)
    {
        $env = config('sage.environment', 'sandbox');
        $clientId = SystemSetting::get('sage_client_id');

        if (! $clientId) {
            return redirect()->route('owner.settings.sage')
                ->with('error', 'Please enter your Sage Client ID first.');
        }

        $state = Str::random(40);
        session(['sage_oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => route('sage.callback'),
            'scope' => config("sage.{$env}.scope"),
            'state' => $state,
            'response_mode' => 'query',
        ]);

        $authUrl = config("sage.{$env}.auth_url");

        return redirect("{$authUrl}?{$params}");
    }

    /**
     * Handle the callback from Sage OAuth.
     * Exchange the authorization code for a bearer token,
     * then create a persistent API key.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            Log::error('Sage OAuth error', [
                'error' => $request->input('error'),
                'description' => $request->input('error_description'),
            ]);

            return redirect()->route('owner.settings.sage')
                ->with('error', 'Sage authorization failed: '.$request->input('error_description', 'Unknown error'));
        }

        $storedState = session('sage_oauth_state');
        if (! $storedState || $request->input('state') !== $storedState) {
            return redirect()->route('owner.settings.sage')
                ->with('error', 'Invalid OAuth state. Please try connecting again.');
        }

        session()->forget('sage_oauth_state');

        $code = $request->input('code');
        if (! $code) {
            return redirect()->route('owner.settings.sage')
                ->with('error', 'No authorization code received from Sage.');
        }

        $env = config('sage.environment', 'sandbox');
        $tokenUrl = config("sage.{$env}.token_url");
        $clientId = SystemSetting::get('sage_client_id');
        $clientSecret = SystemSetting::get('sage_client_secret');

        try {
            $tokenResponse = Http::asForm()->post($tokenUrl, [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => route('sage.callback'),
                'scope' => config("sage.{$env}.scope"),
            ]);

            if (! $tokenResponse->successful()) {
                Log::error('Sage: token exchange failed', [
                    'status' => $tokenResponse->status(),
                    'body' => $tokenResponse->body(),
                ]);

                return redirect()->route('owner.settings.sage')
                    ->with('error', 'Failed to exchange authorization code for token.');
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'] ?? null;

            if (! $accessToken) {
                return redirect()->route('owner.settings.sage')
                    ->with('error', 'No access token received from Sage.');
            }

            $sage = new SageNetworkService;
            $apiKey = $sage->createApiKey($accessToken, 'NRAPA — '.now()->format('Y-m-d H:i'));

            if (! $apiKey) {
                return redirect()->route('owner.settings.sage')
                    ->with('error', 'Connected to Sage but failed to create API key. Please try again.');
            }

            SystemSetting::set('sage_api_key', $apiKey, 'string', 'sage', 'Sage Network API key');
            SystemSetting::set('sage_connected_at', now()->toDateTimeString(), 'string', 'sage', 'Sage connected timestamp');

            $statusCheck = $sage->testConnection();
            if ($statusCheck['success'] && isset($statusCheck['data']['groupName'])) {
                SystemSetting::set('sage_group_key', $statusCheck['data']['groupKey'] ?? '', 'string', 'sage', 'Sage group key');
                SystemSetting::set('sage_group_name', $statusCheck['data']['groupName'] ?? '', 'string', 'sage', 'Sage group name');
            }

            return redirect()->route('owner.settings.sage')
                ->with('success', 'Successfully connected to Sage Network!');

        } catch (\Exception $e) {
            Log::error('Sage OAuth callback exception', ['error' => $e->getMessage()]);

            return redirect()->route('owner.settings.sage')
                ->with('error', 'An error occurred during Sage connection: '.$e->getMessage());
        }
    }
}
