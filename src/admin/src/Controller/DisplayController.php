<?php

namespace YummyNoodles\Component\Todos\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class DisplayController extends BaseController {
    /**
     * The default view for the display method.
     *
     * @var string
     */
    protected $default_view = 'hello';

    public function display($cachable = false, $urlparams = array()) {
        return parent::display($cachable, $urlparams);
    }

}
