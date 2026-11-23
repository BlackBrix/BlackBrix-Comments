<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace BlackBrix\Component\Bbcomments\Administrator\View\Comments;

defined('_JEXEC') or die;

use BlackBrix\Component\Bbcomments\Administrator\Helper\HtmlFilter;
use BlackBrix\Component\Bbcomments\Administrator\Helper\UserFetcher;
use BlackBrix\Component\Bbcomments\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use BlackBrix\Component\Bbcomments\Administrator\Model\CommentsModel;
use Exception;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;

/**
 * HTML view class for listing comments
 *
 * @since 3.0.0
 */
class HtmlView extends BaseHtmlView
{
	use ViewLoadAnyTemplateTrait;

	/**
	 * The active search filters
	 *
	 * @var    array
	 * @since  3.0.0
	 */
	public $activeFilters = [];

	/**
	 * The search tools form
	 *
	 * @var    Form
	 * @since  3.0.0
	 */
	public $filterForm;

	/**
	 * An array of items
	 *
	 * @var    array
	 * @since  3.0.0
	 */
	protected $items = [];

	/**
	 * The pagination object
	 *
	 * @var    Pagination
	 * @since  3.0.0
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var    Registry
	 * @since  3.0.0
	 */
	protected $state;

	/**
	 * Is this view an Empty State
	 *
	 * @var    boolean
	 * @since  3.0.0
	 */
	private $isEmptyState = false;


	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 * @see     self::loadTemplate()
	 * @since   3.0.0
	 */
	public function display($tpl = null)
	{
		/** @var CommentsModel $model */
		$model               = $this->getModel();
		$this->items         = $model->getItems();
		$this->pagination    = $model->getPagination();
		$this->state         = $model->getState();
		$this->filterForm    = $model->getFilterForm();
		$this->activeFilters = $model->getActiveFilters();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		if (!\count($this->items) && $this->isEmptyState = $this->get('IsEmptyState'))
		{
			$this->setLayout('emptystate');
		}


		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Set up Joomla!'s toolbar.
	 *
	 * @return  void
	 * @throws  Exception
	 * @since   5.0.0
	 */
	private function addToolbar(): void
	{
		$canDo = ContentHelper::getActions('com_bbcomments', 'component');
		$user  = UserFetcher::getUser();

		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		ToolbarHelper::title(
			sprintf("%s: <span class=\"fw-bold\">%s</span>", Text::_('COM_BBCOMMENTS'), Text::_('COM_BBCOMMENTS_TITLE_COMMENTS')),
			'icon-bbcomments'
		);

		if (!$this->isEmptyState && $canDo->get('core.edit.state'))
		{
			/** @var DropdownButton $dropdown */
			$dropdown = $toolbar->dropdownButton('status-group')
				->text('JTOOLBAR_CHANGE_STATUS')
				->toggleSplit(false)
				->icon('icon-ellipsis-h')
				->buttonClass('btn btn-action')
				->listCheck(true);

			$childBar = $dropdown->getChildToolbar();

			$childBar->publish('comments.publish')->listCheck(true);

			$childBar->unpublish('comments.unpublish')->listCheck(true);

			$childBar->standardButton('possiblespam')
				->text('COM_BBCOMMENTS_COMMENTS_TOOLBAR_POSSIBLESPAM')
				->task('comments.possiblespam')
				->icon('fas fa-flag')
				->listCheck(true);

			$childBar->confirmButton('reportspam')
				->text('COM_BBCOMMENTS_COMMENTS_TOOLBAR_REPORTSPAM')
				->task('comments.reportspam')
				->icon('fa fa-exclamation-circle')
				->message('COM_BBCOMMENTS_COMMENTS_CONFIRM_REPORTSPAM')
				->listCheck(true);

			$childBar->confirmButton('reportham')
				->text('COM_BBCOMMENTS_COMMENTS_TOOLBAR_REPORTHAM')
				->task('comments.reportham')
				->icon('fa fa-check-square')
				->message('COM_BBCOMMENTS_COMMENTS_CONFIRM_REPORTHAM')
				->listCheck(true);
		}

		if (!$this->isEmptyState && $canDo->get('core.delete'))
		{
			$toolbar->delete('comments.delete')
				->text('JTOOLBAR_DELETE')
				->message('COM_BBCOMMENTS_COMMENTS_CONFIRM_DELETE')
				->listCheck(true);
		}

		if ($user->authorise('core.admin', 'com_bbcomments'))
		{
			$toolbar->link('COM_BBCOMMENTS_IMPORT_TITLE', 'index.php?option=com_bbcomments&view=import')
				->icon('fa fa-upload');
		}

		$toolbar->link('COM_BBCOMMENTS_TITLE_EMAILTEMPLATES', 'index.php?option=com_bbcomments&view=emailtemplates')
			->icon('fa fa-envelope');

		if ($user->authorise('core.admin', 'com_bbcomments') || $user->authorise('core.options', 'com_bbcomments'))
		{
			$toolbar->preferences('com_bbcomments');
		}
	}
}