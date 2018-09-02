<?php

declare(strict_types=1);

namespace Azate\Laravel\WargamingAuth;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;


/**
 * Class WargamingAuth
 *
 * @package Azate\Laravel\WargamingAuth
 */
class WargamingAuth
{
    /**
     * Config repository.
     *
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * Illuminate request class.
     *
     * @var Request
     */
    protected $request;

    /**
     * Illuminate url class.
     *
     * @var UrlGenerator
     */
    protected $urlGenerator;

    /**
     * Region.
     *
     * @var string
     */
    protected $region;

    /**
     * Realm
     *
     * @var string
     */
    protected $realm;

    /**
     * Callback url.
     *
     * @var string
     */
    protected $callbackUrl;

    /**
     * Creates new instance.
     *
     * @param ConfigRepository $configRepository
     * @param Request $request
     * @param UrlGenerator $urlGenerator
     */
    public function __construct(
        ConfigRepository $configRepository,
        Request $request,
        UrlGenerator $urlGenerator
    ) {
        $this->configRepository = $configRepository;
        $this->request = $request;
        $this->urlGenerator = $urlGenerator;

        $this->region = $this->configRepository->get('wargamingAuth.defaultRegion');
        $this->realm = $this->configRepository->get('app.url');
        $this->callbackUrl = $this->urlGenerator->route(
            $this->configRepository->get('wargamingAuth.callbackRoute')
        );
    }

    /**
     * Returns the region.
     *
     * @return string
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * Set the region.
     *
     * @param string $region
     */
    public function setRegion(string $region)
    {
        $this->region = $region;
    }

    /**
     * Returns the realm.
     *
     * @return string
     */
    public function getRealm(): string
    {
        return $this->realm;
    }

    /**
     * Set the realm.
     *
     * @param string $realm
     */
    public function setRealm(string $realm)
    {
        $this->realm = $realm;
    }

    /**
     * Returns the OpenID URL.
     *
     * @return string
     */
    public function getOpenIdUrl(): string
    {
        return 'https://' . $this->region . '.wargaming.net/id/openid/';
    }

    /**
     * Returns the redirect URL.
     *
     * @return string
     */
    public function redirectUrl(): string
    {
        $params = [
            'openid.ax.if_available' => 'ext0,ext1',
            'openid.ax.mode' => 'fetch_request',
            'openid.ax.type.ext0' => 'http://axschema.openid.wargaming.net/spa/id',
            'openid.ax.type.ext1' => 'http://axschema.org/namePerson/friendly',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.mode' => 'checkid_setup',
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.ns.ax' => 'http://openid.net/srv/ax/1.0',
            'openid.realm' => $this->realm,
            'openid.return_to' => $this->callbackUrl,
        ];

        return $this->getOpenIdUrl() . '?' . http_build_query($params, '', '&');
    }

    /**
     * OpenID Positive Assertions.
     *
     * @return bool
     */
    private function isPositiveAssertion(): bool
    {
        $hasFields = $this->request->has([
            'openid_assoc_handle',
            'openid_claimed_id',
            'openid_identity',
            'openid_mode',
            'openid_ns',
            'openid_op_endpoint',
            'openid_response_nonce',
            'openid_return_to',
            'openid_sig',
            'openid_signed',
        ]);

        $isModeIdRes = $this->request->get('openid_mode') === 'id_res';

        return $hasFields && $isModeIdRes;
    }

    /**
     * OpenID Verifying the Return URL.
     *
     * @return bool
     */
    public function verifyingReturnUrl(): bool
    {
        return $this->request->get('openid_return_to') === $this->request->url();
    }

    /**
     * Get param list for OpenID validation
     *
     * @return array
     */
    private function getOpenIdValidationParams(): array
    {
        $params = [];
        $signedParams = explode(',', $this->request->get('openid_signed'));

        foreach ($signedParams as $item) {
            $params['openid.' . $item] = $this->request->get('openid_' . str_replace('.', '_', $item));
        }

        $params['openid.mode'] = 'check_authentication';
        $params['openid.sig'] = $this->request->get('openid_sig');

        return $params;
    }

    /**
     * OpenID Verifying Signatures (Wargaming uses Direct Verification).
     *
     * @return bool
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verifyingSignatures(): bool
    {
        $httpClient = new GuzzleClient;

        $response = $httpClient->request('POST', $this->getOpenIdUrl(), [
            'form_params' => $this->getOpenIdValidationParams(),
        ]);

        $content = $response->getBody()->getContents();

        return strpos($content, 'is_valid:true') !== false;
    }

    /**
     * Process to verify an OpenID assertion.
     *
     * @return bool
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verify(): bool
    {
        return $this->isPositiveAssertion()
            && $this->verifyingReturnUrl()
            && $this->verifyingSignatures();
    }

    /**
     * Returns the user data.
     *
     * @return array
     */
    public function user(): array
    {
        return [
            'id' => $this->request->get('openid_ax_value_ext0_1'),
            'nickname' => $this->request->get('openid_ax_value_ext1_1'),
        ];
    }
}
