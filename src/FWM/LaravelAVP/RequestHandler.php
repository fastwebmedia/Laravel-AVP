<?php namespace FWM\LaravelAVP;

use Illuminate\Http\Request;
use Illuminate\Session\Store as Session;

/**
 * Class RequestHandler
 * @package FWM\LaravelAVP
 */
class RequestHandler {

    /**
     * @var Request
     */
    protected $request;
    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Request $request
     * @param Session $session
     */
    function __construct(Request $request, Session $session)
    {
        $this->request = $request;
        $this->session = $session;
    }

    /**
     * @return array
     */
    public function processDataOfBirth()
    {
        // Get the date of birth that the user submitted
        $dob = null;
        if ($this->request->has('dob')) { // field name is dob when using input type date
            $dob = $this->request->get('dob');
        } elseif ($this->request->has('dob_year') && $this->request->has('dob_month') && $this->request->has('dob_day')) { // field name has _year, _month and _day components if input type select
            $dob = $this->request->get('dob_year') . '-' . $this->request->get('dob_month') . '-' . $this->request->get('dob_day');
        }

	$remember_me = false;
        if ( $this->request->get('remember_me') == "on") {
            $this->session->set('remembered_day', $this->request->get('dob_day'));
            $this->session->set('remembered_month', $this->request->get('dob_month'));
            $this->session->set('remembered_year', $this->request->get('dob_year'));
            $this->session->set('remember_me', "on");
	    $remember_me = true;
        } else {
            $this->session->remove('remembered_day');
            $this->session->remove('remembered_month');
            $this->session->remove('remembered_year');
            $this->session->remove('remember_me');
        }
        // return in an array for validator
        return [
            'dob' => $dob,
            'remember' => $remember_me
        ];
    }

} 
