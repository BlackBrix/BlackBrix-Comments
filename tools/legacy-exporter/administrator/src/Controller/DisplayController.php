<?php
/** Copyright (c) 2026 BlackBrix. GPL-3.0-or-later. */
namespace BlackBrix\Component\Bbengageexport\Administrator\Controller;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use RuntimeException;

final class DisplayController extends BaseController
{
    public function display($cachable = false, $urlparams = []): self
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.admin')) {
            throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $this->input->set('view', 'export');
        parent::display($cachable, $urlparams);
        return $this;
    }
}
