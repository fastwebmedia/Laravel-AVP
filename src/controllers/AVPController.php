<?php namespace FWM\LaravelAVP;

use Carbon\Carbon as Date;
use Illuminate\Routing\Redirector;
use FWM\LaravelAVP\RequestHandler;
use Illuminate\View\Factory as View;
use Illuminate\Session\Store as Session;
use Illuminate\Cookie\CookieJar as Cookie;
use Illuminate\Config\Repository as Config;
use Illuminate\Translation\Translator as Lang;
use Illuminate\Validation\Factory as Validator;

class AVPController extends \BaseController {

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Lang
     */
    protected $lang;

    /**
     * @var Redirector
     */
    protected $redirector;

    /**
     * @var Cookie
     */
    protected $cookie;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var View
     */
    protected $view;

    /**
     * @var Date
     */
    protected $carbon;

    /**
     * @var RequestHandler
     */
    protected $handler;

    /**
     * @param Session $session
     * @param Config $config
     * @param Lang $lang
     * @param Redirector $redirector
     * @param Cookie $cookie
     * @param Validator $validator
     * @param View $view
     * @param Date $carbon
     * @param RequestHandler $handler
     */
    function __construct(Session $session,
                         Config $config,
                        Lang $lang,
                        Redirector $redirector,
                        Cookie $cookie,
                        Validator $validator,
                        View $view,
                        Date $carbon,
                        RequestHandler $handler)
    {
        $this->session = $session;
        $this->config = $config;
        $this->lang = $lang;
        $this->redirector = $redirector;
        $this->cookie = $cookie;
        $this->validator = $validator;
        $this->view = $view;
        $this->carbon = $carbon;
        $this->handler = $handler;
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
        $dob = $this->handler->processDataOfBirth();

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
