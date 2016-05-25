<?php namespace FWM\LaravelAVP;

use Carbon\Carbon;
use Illuminate\Session\Store as Session;
use Illuminate\Cookie\CookieJar as Cookie;
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
     * @var Session
     */
    protected $session;

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
     * @param Session $session
     * @param Carbon $carbon
     * @param Cookie $cookie
     */
    function __construct(Validator $validator,
                         Session $session,
                        Carbon $carbon,
                        Cookie $cookie)
    {
        $this->validator = $validator;
        $this->session = $session;
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
            $canTryAgain = config('laravel-avp.can_try_again');

            $toRedirect = config('laravel-avp.redirect_on_error');
            $redirectURL = config('laravel-avp.redirect_url');
            
            if (substr($data['dob'],0, 4) > date('Y') ) {
		        return redirect()->action('AVPController@agegate')->withErrors($this->validation->messages())->withInput();
	        } else if ($validExceptTooYoung && $toRedirect) {
                return redirect($redirectURL);
            } else if ($validExceptTooYoung && !$canTryAgain) {
                $this->session->put('laravel-avp.previous_too_young', true);
            } else {
                $this->session->keep('url.intended');
            }
            return redirect()->action('AVPController@agegate')->withErrors($this->validation->messages())->withInput();
        }

        return $this->setCookie($data['remember_me']);

    }

    /**
     * @return array
     */
    public function getValidationRules()
    {
        $maxDob = $this->carbon->now()->subYears(config('laravel-avp.minimum_age'))->addDay()->toDateString();
        $minDob = $this->carbon->now()->subYears(config('laravel-avp.maximum_age'))->addDay()->toDateString();

        return ['dob' => 'required|date|date_format:Y-m-d|before:' . $maxDob . '|after:' . $minDob];
    }

    /**
     * @return string
     */
    public function getValidationMessages()
    {
        return trans('laravel-avp::validation.custom');
    }

    /**
     * Set the cookies
     *
     * @return $this
     */
    protected function setCookie($remember_me = false)
    {
        if (config('laravel-avp.cookie_age') == 'forever') {
            // Set a forever cookie saying the user is old enough
            $cookie = $this->cookie->forever(config('laravel-avp.cookie_name'),
                config('laravel-avp.cookie_val'));
        } elseif($remember_me) {
		// Remember for 30 days
	    $this->cookie->make(config('laravel-avp.cookie_name'), config('laravel-avp.cookie_val'), 3600 * 24 * 30);
	} elseif (is_int(config('laravel-avp.cookie_age'))) {
            // Sets a cookie lasting X minutes saying the user is old enough

            $this->cookie->make(config('laravel-avp.cookie_name'),
                config('laravel-avp.cookie_val'),
                config('laravel-avp.cookie_age'));
        } else {
            // Sets a session cookie saying the user is old enough
            $cookie = $this->cookie->make(config('laravel-avp.cookie_name'),
                config('laravel-avp.cookie_val'));
        }

        return redirect()->intended('/')->withCookie($cookie);
    }
}
