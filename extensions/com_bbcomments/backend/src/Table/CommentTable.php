<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace BlackBrix\Component\Bbcomments\Administrator\Table;

use BlackBrix\Component\Bbcomments\Administrator\Helper\UserFetcher;
use BlackBrix\Component\Bbcomments\Administrator\Mixin\TableColumnAliasTrait;
use BlackBrix\Component\Bbcomments\Administrator\Mixin\TableCreateModifyTrait;
use BlackBrix\Component\Bbcomments\Administrator\Mixin\TriggerEventTrait;
use BlackBrix\Component\Bbcomments\Administrator\Model\CommentsModel;
use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;
use RuntimeException;
use UnexpectedValueException;
use function is_array;
use function is_null;

defined('_JEXEC') or die;

/**
 * Table class for BlackBrix Comments Comment records
 *
 * @property int         $id          Comment ID
 * @property int         $parent_id   Parent comment ID
 * @property int         $asset_id    Asset ID being commented on
 * @property string      $body        Comment body
 * @property string|null $name        Commenter's name. Only used when created_by is 0.
 * @property string|null $email       Commenter's email address. Only used when created_by is 0.
 * @property string|null $ip          IP address of the visitor leaving the comment.
 * @property string      $user_agent  User Agent string of the browser of the visitor leaving the comment.
 * @property int         $enabled     Is the comment published?
 * @property string|null $created     When was the comment created?
 * @property int|null    $created_by  User ID of the user who created the comment, 0 for guest comments.
 * @property string|null $modified    When was the comment modified?
 * @property int|null    $modified_by User ID of the user who modified the comment.
 *
 * @since 3.0.0
 */
#[\AllowDynamicProperties]
class CommentTable extends AbstractTable
{
	use TriggerEventTrait;
	use TableColumnAliasTrait;
	use TableCreateModifyTrait
	{
		TableCreateModifyTrait::onBeforeStore as onBeforeStoreCreateModifyAware;
	}

	/**
	 * Object constructor.
	 *
	 * @param   DatabaseDriver            $db          DatabaseDriver object.
	 * @param   DispatcherInterface|null  $dispatcher  Event dispatcher for this table
	 *
	 * @since   3.0.0
	 */
	public function __construct(DatabaseDriver $db, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__bbcomments_comments', 'id', $db, $dispatcher);

		$this->setColumnAlias('published', 'enabled');
		$this->_trackAssets = false;
	}

	/** @inheritdoc */
	public function delete($pk = null)
	{
		$pk = $pk ?? $this->getId();

		$this->triggerEvent('onBeforeDelete', [&$pk]);

		$result = $this->_realDelete($pk);

		$this->triggerEvent('onAfterDelete', [&$result, $pk]);

		return $result;
	}

	/** @inheritdoc */
	public function store($updateNulls = false)
	{
		$isNew = empty($this->id);
		$oldState = $isNew ? null : $this->getStoredPublicationState((int) $this->id);

		$this->triggerEvent($isNew ? 'onBeforeCreate' : 'onBeforeUpdate', [&$updateNulls]);
		$this->triggerEvent('onBeforeStore', [&$updateNulls]);

		$result = $this->_realStore($updateNulls);

		$this->triggerEvent('onAfterStore', [&$result, $updateNulls]);
		$this->triggerEvent($isNew ? 'onAfterCreate' : 'onAfterUpdate', [&$updateNulls]);

		// Also support approvals made by editing a comment's state and saving.
		// Newly created published comments already get their author email from
		// onAfterCreate, so do NOT dispatch twice for new records.
		if ($result && !$isNew && $oldState !== null && $oldState !== 1
			&& (int) $this->enabled === 1)
		{
			$this->notifyFirstPublication();
		}

		return $result;
	}

	/**
	 * Notify plugins only for actual transitions to published state, including
	 * bulk approvals via the Joomla comments list (not just edit-form saves).
	 */
	public function publish($pks = null, $state = 1, $userId = 0)
	{
		// Joomla Table::publish accepts a single ID, a list of IDs, or
		// arrays of primary-key dictionaries (e.g. [['id' => 15]]).
		$keys = empty($pks) ? [(int) $this->id] : (is_array($pks) ? $pks : [$pks]);
		$ids = [];

		foreach ($keys as $key)
		{
			$id = (int) (is_array($key) ? ($key['id'] ?? 0) : $key);

			if ($id > 0)
			{
				$ids[] = $id;
			}
		}

		$ids = array_values(array_unique($ids));
		$previouslyUnpublished = [];

		if ((int) $state === 1)
		{
			foreach ($ids as $id)
			{
				$oldState = $this->getStoredPublicationState($id);

				if ($oldState !== null && $oldState !== 1)
				{
					$previouslyUnpublished[] = $id;
				}
			}
		}

		$result = parent::publish($pks, $state, $userId);

		if ($result && (int) $state === 1)
		{
			foreach ($previouslyUnpublished as $id)
			{
				$published = clone $this;
				$published->reset();

				// Re-read actual persisted state: JTable::publish may skip locked
				// rows, which must never result in spurious email messages.
				if ($published->load($id) && (int) $published->enabled === 1)
				{
					$published->notifyFirstPublication();
				}
			}
		}

		return $result;
	}

	/**
	 * Load the component-specific Email plugin also in the administrator application.
	 * Joomla does not automatically import the `bbcomments` plugin group for
	 * ordinary backend list-publish and edit-save actions.
	 */
	private function notifyFirstPublication(): void
	{
		\Joomla\CMS\Plugin\PluginHelper::importPlugin('bbcomments');
		$this->triggerEvent('onAfterFirstPublish');
	}

	/** Return the database state before a save or list action. */
	private function getStoredPublicationState(int $id): ?int
	{
		if ($id <= 0)
		{
			return null;
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select($db->quoteName('enabled'))
			->from($db->quoteName('#__bbcomments_comments'))
			->where($db->quoteName('id') . ' = ' . $id);
		$value = $db->setQuery($query)->loadResult();

		return $value === null ? null : (int) $value;
	}

	protected function onAfterCreate($junk)
	{
		/**
		 * This is fugly, but Joomla doesn' help me here. It's supposed to set the ID of the new item in the model
		 * state, and I am supposed to be able to get it in the `postSaveHook`.
		 *
		 * SUPPOSED TO.
		 *
		 * Even though I see Joomla calling setState with the correct value... the value is never set in the model's
		 * state. Therefore, I cannot access it from my controller.
		 *
		 * There are many ways to work around that, none of which is architecturally correct except overriding the
		 * model's save() method, copying and pasting Joomla's code, and introducing a new public variable I can access
		 * from postSaveHook. However, that puts a huge maintenance burden so eff that noise.
		 *
		 * THis leaves us with terrible, architecturally incorrect solutions. Among all bad solutions, using a global
		 * variable is the lightest, and easiest to refactor solution out there.
		 *
		 */
		global $bbcomments_newcomment_id;

		$bbcomments_newcomment_id = $this->id;
	}

	/**
	 * Runs after deleting a comment. Used to automatically delete all child comments as well.
	 *
	 * @param   bool  $result  Was the comment deleted?
	 * @param   int   $pk      Primary Key (ID) of the comment deleted
	 *
	 * @throws  Exception
	 * @since   3.0.0
	 */
	protected function onAfterDelete(bool &$result, int $pk): void
	{
		if (!$result)
		{
			return;
		}

		// Get the child comments and delete them as well
		$component = Factory::getApplication()->bootComponent('com_bbcomments');

		if (!$component instanceof MVCFactoryServiceInterface)
		{
			return;
		}

		$factory = $component->getMVCFactory();
		/** @var CommentsModel $commentsModel */
		$commentsModel = $factory->createModel('Comments', 'Administrator', [
			'ignore_request' => true,
		]);

		$commentsModel->setState('filter.parent_id', $pk);
		$commentsModel->setState('list.limit', 30);

		while (true)
		{
			$commentsModel->clearCache();
			$commentsSlice = $commentsModel->getItems();

			if (empty($commentsSlice))
			{
				break;
			}

			$ids = array_map(function ($x) {
				return $x->id;
			}, $commentsSlice);

			$table = clone $this;
			$table->reset();

			foreach ($ids as $id)
			{
				$table->delete($id);
			}
		}
	}

	/**
	 * Runs before checking the table object's data for validity. Performs custom checks.
	 *
	 * @since   3.0.0
	 */
	protected function onBeforeCheck(): void
	{
		// Make sure we have EITHER a user OR both an email and full name
		if (!empty($this->name) && !empty($this->email))
		{
			$this->created_by = 0;
		}

		if (empty($this->name) || empty($this->email))
		{
			$this->name  = null;
			$this->email = null;
		}

		if (empty($this->created_by) && empty($this->name) && empty($this->email))
		{
			throw new RuntimeException(Text::_('COM_BBCOMMENTS_COMMENTS_ERR_NO_NAME_OR_EMAIL'));
		}

		// If we have a guest user, make sure we don't have another user with the same email address
		if (($this->created_by <= 0) && !empty(UserFetcher::getUserIdByEmail($this->email)))
		{
			throw new RuntimeException(Text::sprintf('COM_BBCOMMENTS_COMMENTS_ERR_EMAIL_IN_USE', $this->email));
		}

		// Make sure we have a non–empty comment
		$this->body = trim($this->body ?? '');

		if (empty($this->body))
		{
			throw new RuntimeException(Text::_('COM_BBCOMMENTS_COMMENTS_ERR_COMMENT_REQUIRED'));
		}

		// Check the limits
		$cparams       = ComponentHelper::getParams('com_bbcomments');
		$minLength     = $cparams->get('min_length', 0);
		$maxLength     = $cparams->get('max_length', 0);
		$commentLength = function_exists('mb_strlen') ? mb_strlen($this->body ?? '', '8bit') : strlen($this->body ?? '');

		if ($maxLength > 0 && $minLength > 0 && $maxLength < $minLength)
		{
			$maxLength = 0;
		}

		if ($minLength && $commentLength < $minLength)
		{
			throw new RuntimeException(Text::sprintf('COM_BBCOMMENTS_COMMENTS_ERR_COMMENT_MINLENGTH', $minLength));
		}

		if ($maxLength && $commentLength > $maxLength)
		{
			throw new RuntimeException(Text::sprintf('COM_BBCOMMENTS_COMMENTS_ERR_COMMENT_MAXLENGTH', $maxLength));
		}

		// Unset the modified data if identical to created data
		if (($this->created == $this->modified) && ($this->created_by == $this->modified_by))
		{
			$this->modified    = null;
			$this->modified_by = null;
		}

		// Any parent ID that's empty or a negative integer gets quashed to zero
		$this->parent_id = (empty($this->parent_id) || (is_numeric($this->parent_id) && ($this->parent_id < 0)))
			? null : (int) $this->parent_id;

		// If it's a reply to another comment let's make sure it exists and for the correct asset ID
		if ($this->parent_id !== null)
		{
			// A non-zero parent ID was provided. Try to load the comment.
			$parent = clone $this;
			$parent->reset();

			if (!$parent->load($this->parent_id))
			{
				throw new RuntimeException(Text::_('COM_BBCOMMENTS_COMMENTS_ERR_INVALID_PARENT'));
			}

			// Make sure the parent belongs to the same asset ID we're trying to comment on.
			if ($parent->asset_id != $this->asset_id)
			{
				throw new RuntimeException(Text::_('COM_BBCOMMENTS_COMMENTS_ERR_INVALID_PARENT_ASSET'));
			}
		}
	}

	/**
	 * Method to delete a row from the database table by primary key value.
	 *
	 * @param   mixed  $pk  An optional primary key value to delete.  If not set the instance property value is used.
	 *
	 * @return  boolean  True on success.
	 *
	 * @throws  UnexpectedValueException
	 * @since   3.0.0
	 */
	private function _realDelete($pk = null)
	{
		if (is_null($pk))
		{
			$pk = [];

			foreach ($this->_tbl_keys as $key)
			{
				$pk[$key] = $this->$key;
			}
		}
		elseif (!is_array($pk))
		{
			$pk = [$this->_tbl_key => $pk];
		}

		foreach ($this->_tbl_keys as $key)
		{
			$pk[$key] = is_null($pk[$key]) ? $this->$key : $pk[$key];

			if ($pk[$key] === null)
			{
				throw new UnexpectedValueException('Null primary key not allowed.');
			}

			$this->$key = $pk[$key];
		}

		// Pre-processing by observers
		$event = AbstractEvent::create(
			'onTableBeforeDelete',
			[
				'subject' => $this,
				'pk'      => $pk,
			]
		);
		$this->getDispatcher()->dispatch('onTableBeforeDelete', $event);

		// Delete the row by primary key.
		$query = $this->_db->getQuery(true)
			->delete($this->_tbl);
		$this->appendPrimaryKeys($query, $pk);

		$this->_db->setQuery($query);

		// Check for a database error.
		$this->_db->execute();

		// Post-processing by observers
		$event = AbstractEvent::create(
			'onTableAfterDelete',
			[
				'subject' => $this,
				'pk'      => $pk,
			]
		);
		$this->getDispatcher()->dispatch('onTableAfterDelete', $event);

		return true;
	}

	/**
	 * Method to store a row in the database from the Table instance properties.
	 *
	 * If a primary key value is set the row with that primary key value will be updated with the instance property
	 * values. If no primary key value is set a new row will be inserted into the database with the properties from the
	 * Table instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.0.0
	 */
	private function _realStore($updateNulls = false)
	{
		$result = true;

		$k = $this->_tbl_keys;

		// Pre-processing by observers
		$event = AbstractEvent::create(
			'onTableBeforeStore',
			[
				'subject'     => $this,
				'updateNulls' => $updateNulls,
				'k'           => $k,
			]
		);
		$this->getDispatcher()->dispatch('onTableBeforeStore', $event);

		// We have to unset typeAlias since updateObject / insertObject will try to insert / update all public variables...
		$typeAlias = $this->typeAlias;
		unset($this->typeAlias);

		try
		{
			// If a primary key exists update the object, otherwise insert it.
			if ($this->hasPrimaryKey())
			{
				$this->_db->updateObject($this->_tbl, $this, $this->_tbl_keys, $updateNulls);
			}
			else
			{
				$this->_db->insertObject($this->_tbl, $this, $this->_tbl_keys[0]);
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
			$result = false;
		}

		$this->typeAlias = $typeAlias;

		// Post-processing by observers
		$event = AbstractEvent::create(
			'onTableAfterStore',
			[
				'subject' => $this,
				'result'  => &$result,
			]
		);
		$this->getDispatcher()->dispatch('onTableAfterStore', $event);

		return $result;
	}
}
