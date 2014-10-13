<?php namespace FWM\LaravelAVP;

use \Carbon\Carbon;

class AVPController extends \BaseController {

	/**
	 * Renders the age gate view
	 */
	public function agegate()
	{
		$previousTooYoung = \Session::get('laravel-avp.previous_too_young');
		$view = \View::make(\Config::get('laravel-avp::view'))
			->with(compact('previousTooYoung'));
		

		if (!\Session::has('errors') && $previousTooYoung)
		{
			$messages = \Lang::get('laravel-avp::validation.custom');
			$errorMsg = $messages['dob.previous'];
			$view->withErrors(array('dob' => array($errorMsg)));
		}
		else
		{
			\Session::keep('url.intended');
		}
		return $view;
	}

	/**
	 * Processes the date of birth submitted in the age gate form
	 */
	public function doAgegate()
	{
		$previousTooYoung = \Session::get('laravel-avp.previous_too_young');
		if ($previousTooYoung)
		{
			return \Redirect::action('FWM\LaravelAVP\AVPController@agegate');
		}
		// Get the date of birth that the user submitted
		$dob = null;
		if (\Input::has('dob'))
		{ // field name is dob when using input type date
			$dob = \Input::get('dob');
		}
		elseif (\Input::has('dob_year') && \Input::has('dob_month') && \Input::has('dob_day'))
		{ // field name has _year, _month and _day components if input type select
			$dob = \Input::get('dob_year').'-'.\Input::get('dob_month').'-'.\Input::get('dob_day');
		}

		$maxDob = Carbon::now()->subYears(\Config::get('laravel-avp::minimum_age'))->addDay()->toDateString();

		$validator = \Validator::make(
		    array('dob' => $dob),
		    array('dob' => 'required|date|date_format:Y-m-d|before:'.$maxDob),
			\Lang::get('laravel-avp::validation.custom')
		);

		if ($validator->fails())
		{
			$failed = $validator->failed();
			$validExceptTooYoung = array_get($failed, 'dob.Before');
			$canTryAgain = \Config::get('laravel-avp::can_try_again');

			if ($validExceptTooYoung && \Config::get('laravel-avp:redirect_on_error'))
			{
				\Redirect::to(\Config::get('laravel-avp:redirect_url'));
			}
			else if ($validExceptTooYoung && ! $canTryAgain)
			{
				\Session::put('laravel-avp.previous_too_young', true);
			}
			else
			{
				\Session::keep('url.intended');
			}
		    return \Redirect::action('FWM\LaravelAVP\AVPController@agegate')->withErrors($validator)->withInput();
		}

		if (\Config::get('laravel-avp::cookie_age') == 'forever')
		{
			// Set a forever cookie saying the user is old enough
			$cookie = \Cookie::forever(\Config::get('laravel-avp::cookie_name'), \Config::get('laravel-avp::cookie_val'));
		}
		elseif (is_int(\Config::get('laravel-avp::cookie_age')))
		{
			// Sets a cookie lasting X minutes saying the user is old enough
			Cookie::make(\Config::get('laravel-avp::cookie_name'), \Config::get('laravel-avp::cookie_val'), \Config::get('laravel-avp::cookie_age'));
		}
		else
		{
			// Sets a session cookie saying the user is old enough
			$cookie = \Cookie::make(\Config::get('laravel-avp::cookie_name'), \Config::get('laravel-avp::cookie_val'));
		}
		return \Redirect::intended('/')->withCookie($cookie);
	}

}
