<?php

namespace Erictt\Recaptcha;

use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;

class Recaptcha
{
    const CLIENT_API = 'https://www.google.com/recaptcha/api.js';
    const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * The recaptcha secret key.
     *
     * @var string
     */
    protected $secret;

    /**
     * The recaptcha sitekey key.
     *
     * @var string
     */
    protected $sitekey;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $http;

    /**
     * NoCaptcha.
     *
     * @param string $secret
     * @param string $sitekey
     */
    public function __construct($secret, $sitekey)
    {
        $this->secret = $secret;
        $this->sitekey = $sitekey;
        $this->http = new Client([ 'timeout' => 2.0 ]);
    }

    /**
     * Render HTML captcha.
     *
     * @param array  $domOrScript
     * @param array  $attributes
     *
     * @return string
     */
    public function display($domOrScript = ['dom','script'], $attributes = [])
    {
        $attributes['data-sitekey'] = $this->sitekey;

        $lang = app('config')->get('recaptcha.lang', null);
        if (array_key_exists('lang', $attributes)) {
            $lang = $attributes['lang'];
        }

        $html = "";
        if(in_array('dom', $domOrScript)) {
            $html .= '<div class="g-recaptcha"'.$this->buildAttributes($attributes).'></div>';
        }
        if(in_array('script', $domOrScript)) {
            $html .= '<script src="'.$this->getJsLink($lang).'" async defer></script>';
        }

        return $html;
    }

    /**
     * Verify no-captcha response.
     *
     * @param string $response
     * @param string $clientIp
     *
     * @return bool
     */
    public function verifyResponse($response, $clientIp = null)
    {
        if (empty($response)) {
            return false;
        }

        $response = $this->sendRequestVerify([
            'secret' => $this->secret,
            'response' => $response,
            'remoteip' => $clientIp,
        ]);

        return isset($response['success']) && $response['success'] === true;
    }

    /**
     * Verify no-captcha response by Symfony Request.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function verifyRequest(Request $request)
    {
        return $this->verifyResponse(
            $request->get('g-recaptcha-response'),
            $request->getClientIp()
        );
    }

    /**
     * Get recaptcha js link.
     *
     * @param string $lang
     *
     * @return string
     */
    public function getJsLink($lang = null)
    {
        return $lang ? static::CLIENT_API.'?hl='.$lang : static::CLIENT_API;
    }

    /**
     * Send verify request.
     *
     * @param array $query
     *
     * @return array
     */
    protected function sendRequestVerify(array $query = [])
    {
        $response = $this->http->request('POST', static::VERIFY_URL, [
            'form_params' => $query,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Build HTML attributes.
     *
     * @param array $attributes
     *
     * @return string
     */
    protected function buildAttributes(array $attributes)
    {
        $html = [];

        foreach ($attributes as $key => $value) {
            $html[] = $key.'="'.$value.'"';
        }

        return count($html) ? ' '.implode(' ', $html) : '';
    }
}
