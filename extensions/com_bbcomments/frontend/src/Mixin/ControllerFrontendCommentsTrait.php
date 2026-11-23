<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace BlackBrix\Component\Bbcomments\Site\Mixin;

use BlackBrix\Component\Bbcomments\Administrator\Service\CacheCleaner;
use BlackBrix\Component\Bbcomments\Administrator\Table\CommentTable;
use BlackBrix\Component\Bbcomments\Site\Helper\SignedURL;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use RuntimeException;

defined('_JEXEC') or die;

trait ControllerFrontendCommentsTrait
{
	/**
	 * Checks for a signed URL or a form token in the request.
	 *
	 * @param   string   $method    The request method in which to look for the token key.
	 * @param   boolean  $redirect  Whether to implicitly redirect user to the referrer page on failure or simply
	 *                              return false.
	 *
	 * @return  boolean  True if found and valid, otherwise return false or redirect to referrer page.
	 *
	 * @throws  Exception
	 * @since   1.0.0
	 * @see     Session::checkToken()
	 */
	public function checkToken($method = 'request', $redirect = true): bool
	{
		try
		{
			/** @var CommentTable $table */
			$table = $this->getModel()->getTable('Comment', 'Administrator');
			$token = $this->input->get->getString('token');
			$ids   = $this->input->get('cid', [], 'array');
			$id    = $this->input->get->getInt('id', null);

			$ids     = is_array($ids) ? $ids : [];
			$firstId = array_shift($ids);
			$id      = (is_numeric($firstId) && !empty($firstId)) ? intval($firstId) : $id;

			// Make sure we have a token and a valid comment ID
			if (empty($token) || empty($id) || $table->load($id) === false)
			{
				throw new RuntimeException('', 0xDEAD);
			}

			// If the token is valid we can return true
			$task     = $this->input->get->getCmd('task');
			$email    = $this->input->get->getString('email');
			$expires  = $this->input->get->getInt('expires');
			$asset_id = $table->asset_id;

			if (SignedURL::verifyToken($token, $task, $email, $asset_id, $expires))
			{
				return true;
			}
		}
		catch (RuntimeException $e)
		{
			// If it's not a "fall-through" exception we need to throw it back.
			if ($e->getCode() != 0xDEAD)
			{
				throw $e;
			}
		}

		return parent::checkToken($method, $redirect);
	}

	/**
	 * Adds the comment's ID to the fragment of the redirection.
	 *
	 * This only happens if there is no fragment yet and the ID of the item being edited / published / whatever is not
	 * zero.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private function addCommentFragmentToReturnURL(?int $cid = null): void
	{
		$redirectUrl = $this->getRedirection() ?: $this->getReturnUrl();

		if (empty($redirectUrl))
		{
			return;
		}

		$uri = new Uri($redirectUrl);

		if (!empty($uri->getFragment()))
		{
			return;
		}

		$cid ??= $this->input->get('cid', []);

		if (!is_array($cid))
		{
			$cid = [$cid];
		}

		$cid = empty($cid) ? [] : ArrayHelper::toInteger($cid);
		$id  = empty($cid) ? 0 : array_shift($cid);

		if ($id <= 0)
		{
			return;
		}

		$uri->setFragment('bbcomments-comment-' . $id);
		$uri->setVar('bbcomments_cid', $id);

		$this->setRedirect($uri->toString());
	}


	/**
	 * Clear the Joomla cache for BlackBrix Comments
	 *
	 * @return  void
	 * @throws Exception
	 * @since   1.0.0
	 */
	private function cleanCache(): void
	{
		/** @var CacheCleaner $cacheCleanerService */
		$cacheCleanerService = $this->app->bootComponent('com_bbcomments')
		                                 ->getCacheCleanerService();

		$cacheCleanerService
		          ->clearGroups(
			          [
				          'com_bbcomments',
			          ],
			          'onCommentsClearCache'
		          );

		$cacheCleanerService
		          ->clearGroups(
			          [
				          'com_content',
			          ]
		          );
	}

	/**
	 * Disables the Joomla cache for this response.
	 *
	 * @return  void
	 * @since   1.0.0
	 * @see     \Joomla\CMS\MVC\Controller\FormController::edit()
	 */
	private function disableJoomlaCache(): void
	{
		try
		{
			/** @var CMSApplication $app */
			$app = Factory::getApplication();
		}
		catch (Exception $e)
		{
			return;
		}

		if (!method_exists($app, 'allowCache'))
		{
			return;
		}

		$app->allowCache(false);
	}
}