<?php

declare(strict_types=1);

namespace Azate\LaravelWargamingAuth;

use Illuminate\Http\Request;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Class WargamingAuth
 *
 * @package Azate\LaravelWargamingAuth
 */
final class WargamingAuth
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var GuzzleClient
     */
    private $httpClient;

    /**
     * Create a new WargamingAuth instance
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->httpClient = new GuzzleClient;
    }

    /**
     * Returns the OpenID URL
     *
     * @return string
     */
    public function getOpenIdUrl(): string
    {
        return 'https://ru.wargaming.net/id/openid/';
    }

    /**
     * Returns the Wargaming login URL
     *
     * @return string
     */
    public function getAuthUrl(): string
    {
        return $this->buildAuthUrl(route(config('wargaming_auth.redirect_route')));
    }

    /**
     * Build the Wargaming login URL
     *
     * @param string|null $returnTo
     *
     * @return string
     */
    private function buildAuthUrl(string $returnTo = null): string
    {
        $params = [
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.mode' => 'checkid_setup',
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.return_to' => $returnTo,
        ];

        return $this->getOpenIdUrl() . '?' . http_build_query($params, '', '&');
    }

    /**
     * Returns the redirect response to login
     *
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function redirect()
    {
        return redirect($this->getAuthUrl());
    }

    /**
     * Validates if the request object has required stream attributes.
     *
     * @return bool
     */
    private function requestIsValid(): bool
    {
        return $this->request->has('openid_assoc_handle')
            && $this->request->has('openid_sig')
            && $this->request->has('openid_signed');
    }

    /**
     * Get param list for OpenID validation
     *
     * @return array
     */
    private function getOpenIdValidationParams(): array
    {
        $params = [
            'openid.assoc_handle' => $this->request->get('openid_assoc_handle'),
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.sig' => $this->request->get('openid_sig'),
            'openid.signed' => $this->request->get('openid_signed'),
        ];

        $signedParams = explode(',', $this->request->get('openid_signed'));

        foreach ($signedParams as $item) {
            $params['openid.' . $item] = $this->request->get('openid_' . str_replace('.', '_', $item));
        }

        $params['openid.mode'] = 'check_authentication';

        return $params;
    }

    /**
     * Checks
     *
     * @return bool
     */
    public function validate(): bool
    {
        if (!$this->requestIsValid()) {
            return false;
        }

        $response = $this->httpClient->request('POST', $this->getOpenIdUrl(), [
            'form_params' => $this->getOpenIdValidationParams(),
        ]);

        return strstr($response->getBody()->getContents(), 'is_valid:true') !== false;
    }

    /**
     * Parse user data
     *
     * @return array
     */
    private function parseUserData(): array
    {
        preg_match('#/id/([0-9]+)-(.*)/#', $this->request->get('openid_identity'), $matches);

        return [
            'id' => $matches[1],
            'nickname' => $matches[2],
        ];
    }

    /**
     * Returns the user data
     *
     * @return array
     */
    public function user(): array
    {
        $data = $this->parseUserData();

        return [
            'id' => $data['id'],
            'nickname' => $data['nickname'],
        ];
    }
}
