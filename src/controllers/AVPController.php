<?php namespace FWM\LaravelAVP;

use FWM\LaravelAVP\Validation;
use Illuminate\Routing\Redirector;
use FWM\LaravelAVP\RequestHandler;
use Illuminate\View\Factory as View;
use Illuminate\Session\Store as Session;
use Illuminate\Config\Repository as Config;
use Illuminate\Translation\Translator as Lang;

class AVPController extends \BaseController
{

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
     * @var View
     */
    protected $view;

    /**
     * @var RequestHandler
     */
    protected $handler;

    /**
     * @var Validation
     */
    protected $validation;

    /**
     * @param Session $session
     * @param Config $config
     * @param Lang $lang
     * @param Redirector $redirector
     * @param View $view
     * @param RequestHandler $handler
     * @param Validation $validation
     */
    function __construct(Session $session,
                         Config $config,
                         Lang $lang,
                         Redirector $redirector,
                         View $view,
                         RequestHandler $handler,
                         Validation $validation)
    {
        $this->session = $session;
        $this->config = $config;
        $this->lang = $lang;
        $this->redirector = $redirector;
        $this->view = $view;
        $this->handler = $handler;
        $this->validation = $validation;
        
        parent::__construct();
    }

    /**
     * Renders the age gate view
     */
    public function agegate()
    {
        $previousTooYoung = $this->session->get('laravel-avp.previous_too_young');
        $view = $this->view->make($this->config->get('laravel-avp::view'))
            ->with(compact('previousTooYoung'));

        if (!$this->session->has('errors') && $previousTooYoung) {
            $messages = $this->lang->get('laravel-avp::validation.custom');
            $errorMsg = $messages['dob.previous'];
            $view->withErrors(['dob' => [$errorMsg]]);
        } else {
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
        if ($previousTooYoung) {
            return $this->$redirector->action('FWM\LaravelAVP\AVPController@agegate');
        }

        // Get the date of birth that the user submitted
        $dob = $this->handler->processDataOfBirth();

        return $this->validation->validate($dob);

    }

}
