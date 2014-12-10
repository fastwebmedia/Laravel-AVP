<?php namespace spec\FWM\LaravelAVP;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Illuminate\Config\Repository as Config;
use Illuminate\Http\Request;
use \Illuminate\Session\Store as Session;
use Illuminate\Routing\Redirector as Redirect;


class LaravelAVPFilterSpec extends ObjectBehavior
{
	function it_is_initializable()
	{
		$this->shouldHaveType('FWM\LaravelAVP\LaravelAVPFilter');
	}

    function let(Request $request, Config $config, Session $session, Redirect $redirect)
    {
		$this->beAnInstanceOf('FWM\LaravelAVP\LaravelAVPFilter');
	    $this->beConstructedWith($request, $config, $session, $redirect);
    }

}
