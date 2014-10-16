<?php namespace FWM\LaravelAVP;

use Illuminate\Session\Store as Session;
use Illuminate\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Translation\Translator as Lang;
use Illuminate\Routing\Redirector;
use Illuminate\Cookie\CookieJar as Cookie;
use Illuminate\Validation\Factory as Validator;
use Illuminate\View\Factory as View;
use Carbon\Carbon as Date;


class AVPController extends \BaseController {

    protected $session;

    function __construct(Session $session,
                         Config $config,
                         Request $request,
                        Lang $lang,
                        Redirector $redirector,
                        Cookie $cookie,
                        Validator $validator,
                        View $view, Date $carbon)
    {
        $this->session = $session;
        $this->config = $config;
        $this->request = $request;
        $this->lang = $lang;
        $this->redirector = $redirector;
        $this->cookie = $cookie;
        $this->validator = $validator;
        $this->view = $view;
        $this->carbon = $carbon;
    }

    /**
	 * Renders the age gate view
	 */
	public function agegate()
	{
		$previousTooYoung = $this->session->get('laravel-avp.previous_too_young');
		$view = $this->view->make($this->config->get('laravel-avp::view'))
			->with(compact('previousTooYoung'));
		

		if (!$this->session->has('errors') && $previousTooYoung)
		{
			$messages = $this->lang->get('laravel-avp::validation.custom');
			$errorMsg = $messages['dob.previous'];
			$view->withErrors(array('dob' => array($errorMsg)));
		}
		else
		{
            $this->session->keep('url.intended');
		}
		return $view;
	}

	/**
	 * Processes the date of birth submitted in the age gate form
	 */
	public function doAgegate()
	{
		$previousTooYoung = $this->session->get('laravel-avp.previous_too_young');
		if ($previousTooYoung)
		{
			return $this->$redirector->action('FWM\LaravelAVP\AVPController@agegate');
		}
		// Get the date of birth that the user submitted
		$dob = null;
		if ($this->request->has('dob'))
		{ // field name is dob when using input type date
			$dob = $this->request->get('dob');
		}
		elseif ($this->request->has('dob_year') && $this->request->has('dob_month') && $this->request->has('dob_day'))
		{ // field name has _year, _month and _day components if input type select
			$dob = $this->request->get('dob_year').'-'. $this->request->get('dob_month').'-'. $this->request->get('dob_day');
		}

		$maxDob = $this->carbon->now()->subYears($this->config->get('laravel-avp::minimum_age'))->addDay()->toDateString();
		$minDob = $this->carbon->now()->subYears($this->config->get('laravel-avp::maximum_age'))->addDay()->toDateString();

		$validator = $this->validator->make(
		    array('dob' => $dob),
		    array('dob' => 'required|date|date_format:Y-m-d|before:'.$maxDob.'|after:'.$minDob),
			$this->lang->get('laravel-avp::validation.custom')
		);

		if ($validator->fails())
		{
			$failed = $validator->failed();
			$validExceptTooYoung = array_get($failed, 'dob.Before');
			$canTryAgain = $this->config->get('laravel-avp::can_try_again');

			$toRedirect = $this->config->get('laravel-avp::redirect_on_error');
        		$redirectURL = $this->config->get('laravel-avp::redirect_url');

        		if ($validExceptTooYoung && $toRedirect )
			{
				return $this->redirector->to($redirectURL);
			}
			else if ($validExceptTooYoung && ! $canTryAgain)
			{
                $this->session->put('laravel-avp.previous_too_young', true);
			}
			else
			{
                $this->session->keep('url.intended');
			}
		    return $this->redirector->action('FWM\LaravelAVP\AVPController@agegate')->withErrors($validator)->withInput();
		}

		if ($this->config->get('laravel-avp::cookie_age') == 'forever')
		{
			// Set a forever cookie saying the user is old enough
			$cookie = $this->cookie->forever($this->config->get('laravel-avp::cookie_name'), $this->config->get('laravel-avp::cookie_val'));
		}
		elseif (is_int($this->config->get('laravel-avp::cookie_age')))
		{
			// Sets a cookie lasting X minutes saying the user is old enough
			$this->cookie->make($this->config->get('laravel-avp::cookie_name'), $this->config->get('laravel-avp::cookie_val'), $this->config->get('laravel-avp::cookie_age'));
		}
		else
		{
			// Sets a session cookie saying the user is old enough
			$cookie = $this->cookie->make($this->config->get('laravel-avp::cookie_name'), $this->config->get('laravel-avp::cookie_val'));
		}
		return $this->redirector->intended('/')->withCookie($cookie);
	}

}
