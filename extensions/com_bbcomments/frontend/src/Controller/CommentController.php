<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace BlackBrix\Component\Bbcomments\Site\Controller;

defined('_JEXEC') or die;

use BlackBrix\Component\Bbcomments\Administrator\Controller\CommentController as AdminCommentController;
use BlackBrix\Component\Bbcomments\Administrator\Helper\UserFetcher;
use BlackBrix\Component\Bbcomments\Administrator\Mixin\ControllerRedirectionTrait;
use BlackBrix\Component\Bbcomments\Administrator\Mixin\ControllerReturnURLTrait;
use BlackBrix\Component\Bbcomments\Administrator\Mixin\ControllerReusableModelsTrait;
use BlackBrix\Component\Bbcomments\Administrator\Table\CommentTable;
use BlackBrix\Component\Bbcomments\Site\Mixin\ControllerFrontendCommentsTrait;
use BlackBrix\Component\Bbcomments\Site\Model\CommentModel;
use BlackBrix\Component\Bbcomments\Site\Helper\Meta;
use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use RuntimeException;

class CommentController extends AdminCommentController
{
	use ControllerFrontendCommentsTrait;
	use ControllerRedirectionTrait;
	use ControllerReturnURLTrait;
	use ControllerReusableModelsTrait;

	public function batch($model)
	{
		throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
	}

	public function editAssociations()
	{
		throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
	}

	/**
	 * Method to check if you can edit an existing record.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key; default is id.
	 *
	 * @return  boolean
	 *
	 * @throws  Exception
	 * @since   3.0.0
	 */
	protected function allowEdit($data = [], $key = 'id')
	{
		if (parent::allowEdit($data, $key))
		{
			return true;
		}

		/**
		 * If I am here the user does not have the core.edit permissions. I will check if the user has core.edit.own and
		 * the referenced comment belongs to the user.
		 */
		$user = UserFetcher::getUser();

		if ($user->guest || !$user->authorise('core.edit.own', 'com_bbcomments'))
		{
			return false;
		}

		/** @var CommentTable $table */
		$table = $this->getModel()->getTable('Comment', 'Administrator');
		$id    = $data[$key] ?? null;

		if (empty($id) || !$table->load($id))
		{
			return false;
		}

		return $table->created_by == $user->id;
	}

	protected function onAfterApply()
	{
		$this->applyReturnUrl();

		global $bbcomments_newcomment_id;

		if (isset($bbcomments_newcomment_id) && !empty($bbcomments_newcomment_id))
		{
			$this->addCommentFragmentToReturnURL($bbcomments_newcomment_id);
		}

		$this->cleanCache();
	}

	protected function onAfterSave()
	{
		$this->applyReturnUrl();

		global $bbcomments_newcomment_id;

		if (isset($bbcomments_newcomment_id) && !empty($bbcomments_newcomment_id))
		{
			$this->addCommentFragmentToReturnURL($bbcomments_newcomment_id);
		}

		$this->cleanCache();
	}

	protected function onBeforeMain()
	{
		$this->disableJoomlaCache();
		$this->getView()->returnUrl = $this->getRedirection() ?: $this->getReturnUrl() ?: '';
	}

	protected function postSaveHook(BaseDatabaseModel $model, $validData = [])
	{
		// If the user was unsubscribed from comments we need to resubscribe them
		$user = UserFetcher::getUser();
		$model->resubscribeUser((int) ($validData['asset_id'] ?? 0), $user, $validData['email'] ?? null);

		// The moderation setting alone is insufficient: moderators can publish immediately.
		// Use the status of the record that was actually saved.
		global $bbcomments_newcomment_id;
		$id = (int) ($bbcomments_newcomment_id ?? ($validData['id'] ?? 0));
		$status = null;
		if ($id > 0)
		{
			try
			{
				$table = $model->getTable('Comment', 'Administrator');
				if ($table->load($id))
				{
					$status = (int) $table->enabled;
				}
			}
			catch (\Throwable $e)
			{
				// Preserve the successful save even if the feedback lookup fails.
			}
		}

		$isGuestbook = false;
		try
		{
			$meta = Meta::getAssetAccessMeta((int) ($validData['asset_id'] ?? 0), true);
			$isGuestbook = ($meta['parameters'] ?? null) instanceof \Joomla\Registry\Registry
				&& $meta['parameters']->get('comments_context', 'comment') === 'guestbook';
		}
		catch (\Throwable $e)
		{
			// Fall back to article terminology.
		}

		$messageKey = match ($status) {
			0 => $isGuestbook ? 'COM_BBCOMMENTS_GUESTBOOK_MSG_MODERATION' : 'COM_BBCOMMENTS_COMMENTS_MSG_MODERATION',
			1 => $isGuestbook ? 'COM_BBCOMMENTS_GUESTBOOK_MSG_PUBLISHED' : 'COM_BBCOMMENTS_COMMENTS_MSG_PUBLISHED',
			default => 'COM_BBCOMMENTS_COMMENTS_MSG_SUCCESS',
		};
		$this->setMessage(Text::_($messageKey), 'success');

		parent::postSaveHook($model, $validData);
	}

}