<?php

return array(

	/**
	 * The URI of the agegate
	 */
	'agegate_uri' => 'check',

	/**
	 * The minimum and maximum age to access the site
	 */
	'minimum_age' => 18,
    'maximum_age' => 114,

	/**
	 * The input type to use. Choices are:
	 * "date" for html5 <input type="date" />
	 * "select" for 3 <select> tags for day, month and year
	 */
	'input_type' => 'date',

	/**
	 * The name of the cookie to set. Change this to whatever you want
	 */
	'cookie_name' => 'age_ok',

	/**
	 * The URL to redirect to on failure
	*/
	'redirect_on_error' => true,
	'redirect_url' => "http://www.drinkaware.co.uk/why-am-i-here",

	/**
	 * The value of the cookie to set. Change this to something unique
	 */
	'cookie_val' => 'validated',

	/**
	 * The age of the cookie to set. Options are 'forever', an integer (minutes) or the default is for the session
	 */
	'cookie_age' => '3600',

	/**
	 * Determines whether the user can try again or not
	 */
	'can_try_again' => true,

	/**
	 * The view that should be rendered for the agegate. You can use the bundled view, or specify your own and use
	 * @include('laravel-agegate::agegate') to get the agegate form and validation errors
	 */
	'view' => 'laravel-avp::avp',

	'allowed_routes' => array(
		'privacy-policy',
		'cookies',
		'terms'
	),
	
	/**
	 *
	 */
	'allowed_user_agents' => array(
		'ipv4' => array(
			'80.74.134.135'
		),
		'strings' => array(
			'bot',
			'slurp',
			'crawl',
			'spider',
			'yahoo',
			'facebookexternalhit',
		),

	),

);
