<?php namespace FWM\LaravelAVP;

use Illuminate\Http\Request;

class RequestHandler {

    protected $request;

    function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function processDataOfBirth()
    {
        // Get the date of birth that the user submitted
        $dob = null;
        if ($this->request->has('dob')) { // field name is dob when using input type date
            $dob = $this->request->get('dob');
        } elseif ($this->request->has('dob_year') && $this->request->has('dob_month') && $this->request->has('dob_day')) { // field name has _year, _month and _day components if input type select
            $dob = $this->request->get('dob_year') . '-' . $this->request->get('dob_month') . '-' . $this->request->get('dob_day');
        }

        // return in an array for validator
        return [
            'dob' => $dob
        ];
    }

} 