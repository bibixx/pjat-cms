<?php

namespace YummyNoodles\Component\Todos\Site\View\Hello;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView {


    /**
     * Display the view
     *
     * @param   string  $template  The name of the layout file to parse.
     * @return  void
     */
    public function display($template = null) {
        // Call the parent display to display the layout file
        parent::display($template);
    }

}
