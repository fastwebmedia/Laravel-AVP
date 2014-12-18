<?php namespace FWM\LaravelAVP;

use Illuminate\Config\Repository as Config;
use Illuminate\Http\Request;
use \Illuminate\Session\Store as Session;
use Illuminate\Routing\Redirector as Redirect;

/**
 * Class LaravelAVPFilter
 *
 * A Laravel AVP Filter
 */
class LaravelAVPFilter {

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * @var Session
	 */
	protected $session;

	/**
	 * @var Redirect
	 */
	protected $redirect;

	/**
	 * @param Request $request
	 * @param Config $config
	 * @param Session $session
	 * @param Redirect $redirect
	 */
	public function __construct(Request $request, Config $config, Session $session, Redirect $redirect)
	{
		$this->request = $request;
		$this->config = $config;
		$this->session = $session;
		$this->redirect = $redirect;
	}

	/**
	 * @return mixed
	 */
	public function filter()
	{
		if (!$this->isAllowed())
		{
			$this->rememberDesiredUrl();
			return $this->getAgeGateRedirect();
		}
	}

	
	/**
	 * @return bool
	 */
	public function isAllowed()
	{
		if ($this->isAllowedRoute())
		{
			return true;
		}
		
		if ($this->isAgeCookieOK())
		{
			return true;
		}
		if ($this->isUserAgentAllowed())
		{
			return true;
		}
		if ($this->isIPAddressAllowed())
		{
			return true;
		}
	}

        /**
	 * @return bool
	 */
	public function isAllowedRoute()
	{
		$routes = $this->config->get('laravel-avp::allowed_routes');
		return in_array($this->request->path(), $routes);
		
	}
	
	/**
	 * @return bool
	 */
	public function isAgeCookieOK()
	{
		$cookieVal = $this->request->cookie($this->config->get('laravel-avp::cookie_name'));
		$whatItShouldBe = $this->config->get('laravel-avp::cookie_val');
		return $cookieVal == $whatItShouldBe;
	}

	/**
	 * @return int
	 */
	public function isUserAgentAllowed()
	{
		// We currently only use one type of matching, so just return
		return $this->userAgentContainsAllowedString();
	}

	/**
	 * @return bool
	 */
	public function isIPAddressAllowed()
	{
		$ips = $this->getAllowedAddresses();
		return in_array($this->getRemoteIPAddress(), $ips);
	}

	/**
	 * @return string
	 */
	public function getRemoteIPAddress()
	{
		return strtolower($this->request->server('REMOTE_ADDR'));
	}

	/**
	 * @return mixed
	 */
	public function getAllowedAddresses()
	{
		return $this->config->get('laravel-avp::allowed_user_agents.ipv4');
	}

	/**
	 * @return mixed
	 */
	public function getAllowedUserAgentStrings()
	{
		return $this->config->get('laravel-avp::allowed_user_agents.strings');
	}

	/**
	 * @return string
	 */
	public function getLowerUserAgent()
	{
		return strtolower($this->request->server('HTTP_USER_AGENT'));
	}

	/**
	 * @return int
	 */
	public function userAgentContainsAllowedString()
	{
		$strings = $this->getAllowedUserAgentStrings();
		$escape = function(&$val)
		{
			$val = preg_quote($val, '/');
		};
		array_walk($strings, $escape);
		$pattern = '/'.implode('|', $strings).'/';
		$userAgent = $this->getLowerUserAgent();
		return preg_match($pattern, $userAgent);
	}

	/**
	 *
	 */
	public function rememberDesiredUrl()
	{
		$desiredUrl = $this->request->fullUrl();
		$this->session->flash('url.intended', $desiredUrl);
	}

	/**
	 * @return mixed
	 */
	public function getAgeGateRedirect()
	{
		$url = $this->config->get('laravel-avp::agegate_uri').'?'.$this->request->server('QUERY_STRING');
	    return $this->redirect->to($url);
	}

}
