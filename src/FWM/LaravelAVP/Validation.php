<?php namespace FWM\LaravelAVP;

use Carbon\Carbon;
use Illuminate\Routing\Redirector;
use Illuminate\Session\Store as Session;
use Illuminate\Cookie\CookieJar as Cookie;
use Illuminate\Config\Repository as Config;
use Illuminate\Translation\Translator as Lang;
use Illuminate\Validation\Factory as Validator;


class Validation {

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var
     */
    protected $validation;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Redirector
     */
    protected $redirector;

    /**
     * @var Carbon
     */
    protected $carbon;

    /**
     * @var Cookie
     */
    protected $cookie;

    /**
     * @param Validator $validator
     * @param Config $config
     * @param Session $session
     * @param Redirector $redirector
     * @param Lang $lang
     * @param Carbon $carbon
     * @param Cookie $cookie
     */
    function __construct(Validator $validator,
                         Config $config,
                         Session $session,
                         Redirector $redirector,
                         Lang $lang,
                        Carbon $carbon,
                        Cookie $cookie)
    {
        $this->validator = $validator;
        $this->config = $config;
        $this->session = $session;
        $this->redirector = $redirector;
        $this->lang = $lang;
        $this->carbon = $carbon;
        $this->cookie = $cookie;

    }

    /**
     * Validate what is passed into the age gate
     *
     * @param array $data
     * @return $this|Validation|\Illuminate\Http\RedirectResponse
     */
    public function validate(array $data)
    {
        $this->validation = $this->validator->make(
            $data,
            $this->getValidationRules(),
            $this->getValidationMessages()
        );

        if ( $this->validation->fails() )
        {
            $failed = $this->validation->failed();
            
            $validExceptTooYoung = array_get($failed, 'dob.Before');
            $canTryAgain = $this->config->get('laravel-avp::can_try_again');

            $toRedirect = $this->config->get('laravel-avp::redirect_on_error');
            $redirectURL = $this->config->get('laravel-avp::redirect_url');

            if ($validExceptTooYoung && $toRedirect) {
                return $this->redirector->to($redirectURL);
            } else if ($validExceptTooYoung && !$canTryAgain) {
                $this->session->put('laravel-avp.previous_too_young', true);
            } else {
                $this->session->keep('url.intended');
            }
            return $this->redirector->action('FWM\LaravelAVP\AVPController@agegate')->withErrors($this->validation->messages())->withInput();
        }

        return $this->setCookie();

    }

    /**
     * @return array
     */
    public function getValidationRules()
    {
        $maxDob = $this->carbon->now()->subYears($this->config->get('laravel-avp::minimum_age'))->addDay()->toDateString();
        $minDob = $this->carbon->now()->subYears($this->config->get('laravel-avp::maximum_age'))->addDay()->toDateString();

        return ['dob' => 'required|date|date_format:Y-m-d|before:' . $maxDob . '|after:' . $minDob];
    }

    /**
     * @return string
     */
    public function getValidationMessages()
    {
        return $this->lang->get('laravel-avp::validation.custom');
    }

    /**
     * Set the cookies
     *
     * @return $this
     */
    protected function setCookie()
    {
        if ($this->config->get('laravel-avp::cookie_age') == 'forever') {
            // Set a forever cookie saying the user is old enough
            $cookie = $this->cookie->forever($this->config->get('laravel-avp::cookie_name'),
                $this->config->get('laravel-avp::cookie_val'));
        } elseif (is_int($this->config->get('laravel-avp::cookie_age'))) {
            // Sets a cookie lasting X minutes saying the user is old enough
            $this->cookie->make($this->config->get('laravel-avp::cookie_name'),
                $this->config->get('laravel-avp::cookie_val'),
                $this->config->get('laravel-avp::cookie_age'));
        } else {
            // Sets a session cookie saying the user is old enough
            $cookie = $this->cookie->make($this->config->get('laravel-avp::cookie_name'),
                $this->config->get('laravel-avp::cookie_val'));
        }
        return $this->redirector->intended('/')->withCookie($cookie);
    }
}
