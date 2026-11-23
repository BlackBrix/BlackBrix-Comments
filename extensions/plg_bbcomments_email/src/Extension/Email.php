<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace BlackBrix\Plugin\Bbcomments\Email\Extension;

use BlackBrix\Component\Bbcomments\Administrator\Helper\Avatar;
use BlackBrix\Component\Bbcomments\Administrator\Helper\Html2Text;
use BlackBrix\Component\Bbcomments\Administrator\Helper\HtmlFilter;
use BlackBrix\Component\Bbcomments\Administrator\Helper\TemplateEmails;
use BlackBrix\Component\Bbcomments\Administrator\Helper\UserFetcher;
use BlackBrix\Component\Bbcomments\Administrator\Table\CommentTable;
use BlackBrix\Component\Bbcomments\Site\Helper\Meta;
use BlackBrix\Component\Bbcomments\Site\Helper\SignedURL;
use DateTimeZone;
use Exception;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\WebApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

defined('_JEXEC') or die;

class Email extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;

	/**
	 * Disallow registering legacy listeners since we use SubscriberInterface
	 *
	 * @var   bool
	 * @since 3.0.0
	 */
	protected $allowLegacyListeners = false;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   3.0.3
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onComBbcommentsCommentTableAfterCreate'       => 'sendEmailsAfterPostCreation',
			'onComBbcommentsCommentTableAfterFirstPublish' => 'sendAuthorEmailAfterApproval',
		];
	}

	/**
	 * Automatically triggered right after BlackBrix Comments saves a NEW comment record to its database table.
	 *
	 * @param   Event  $event  The event we are handling
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function sendEmailsAfterPostCreation(Event $event): void
	{
		/** @var CommentTable $comment The comment table being saved */
		[$comment] = array_values($event->getArguments());

		// No emails in non-web applications, please
		if (!($this->getApplication() instanceof WebApplication))
		{
			return;
		}

		$meta              = Meta::getAssetAccessMeta($comment->asset_id, true);
		$notifyAuthor      = $meta['parameters']->get('comments_notify_author', 1);
		$notifyUsers       = $meta['parameters']->get('comments_notify_users', 1);
		$honorUnsubscribed = true;

		switch ($comment->enabled)
		{
			// Unpublished or Spam: email the Comment Managers
			case 0:
			case -3:
			default:
				// If the comment is marked as spam and managers_notify_spam is disabled no email will be sent.
				$doIt = $comment->enabled != -3 ||
				        $this->params->get('managers_notify_spam', 0) == 1;

				if (!$doIt)
				{
					return;
				}

				$recipients        = $this->getCommentManagerRecipients();
				$type              = ($comment->enabled == 0) ? 'com_bbcomments.manage' : 'com_bbcomments.spam';
				$honorUnsubscribed = false;

				break;

			// Published: notify users taking part in the conversation
			case 1:
				$recipients     = $notifyUsers ? $this->getUserRecipients($comment) : [];
				$type           = 'com_bbcomments.notify';
				$notifyManagers = $this->params->get('managers_notify', 0) == 1;

				/**
				 * Notify the content author unless he's already being notified as a user
				 */
				if ($notifyAuthor)
				{
					$this->sendEmailMessages('com_bbcomments.notify_author', $comment, $this->getRecipientsArrayDiff(
						[$meta['author_email'] => $meta['author_name']], $recipients
					));
				}

				if ($notifyManagers)
				{
					/**
					 * Notify managers unless they are being notified EITHER as a user OR as a content author
					 */
					$managerRecipients = $this->getRecipientsArrayDiff($this->getCommentManagerRecipients(), $recipients);

					if ($notifyAuthor)
					{
						$managerRecipients = $this->getRecipientsArrayDiff(
							$this->getCommentManagerRecipients(), [$meta['author_email'] => $meta['author_name']]
						);
					}

					$this->sendEmailMessages('com_bbcomments.notify_managers', $comment, $managerRecipients);
				}

				break;
		}

		$this->sendEmailMessages($type, $comment, $recipients, $honorUnsubscribed);
	}

	/**
	 * Inform the article author when a previously pending comment is approved.
	 * The table emits this event only for an actual transition to published.
	 * This is intentionally separate from the new-comment event, so a pending
	 * comment does not notify its author until it becomes publicly visible.
	 */
	public function sendAuthorEmailAfterApproval(Event $event): void
	{
		[$comment] = array_values($event->getArguments());

		if (!($comment instanceof CommentTable) || (int) $comment->enabled !== 1
			|| !($this->getApplication() instanceof WebApplication))
		{
			return;
		}

		$meta = Meta::getAssetAccessMeta((int) $comment->asset_id, true);

		if (empty($meta['parameters']) || !$meta['parameters']->get('comments_notify_author', 1))
		{
			return;
		}

		$email = trim((string) ($meta['author_email'] ?? ''));
		$name = (string) ($meta['author_name'] ?? '');

		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			return;
		}

		// Keep the existing unsubscribe handling and language-aware mail template.
		// Author and moderator may intentionally be the same account.
		$this->sendEmailMessages('com_bbcomments.notify_author', $comment, [$email => $name]);
	}

	/**
	 * Returns the ID of all Joomla user groups which are Comment Managers.
	 *
	 * Any user group that has the core.edit.state privilege on the component is considered a Comment Manager.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	protected function getCommentManagerGroups(): array
	{
		// Get all groups
		$db    = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([$db->qn('id')])
			->from($db->qn('#__usergroups'));

		$adminGroups = $db->setQuery($query)->loadColumn(0) ?? [];

		return array_filter($adminGroups, function ($group) {
			return
				Access::checkGroup($group, 'core.edit.state', 'com_bbcomments') ||
				Access::checkGroup($group, 'core.edit.state') ||
				Access::checkGroup($group, 'core.admin');
		});
	}

	/**
	 * Returns the email addresses and names of comment managers.
	 *
	 * Only the email addresses of comments managers with Send Email set to Yes will be returned.
	 *
	 * @return  array  User full names keyed by email
	 * @since   1.0.0
	 */
	protected function getCommentManagerRecipients(): array
	{
		$managerGroups = $this->getCommentManagerGroups();

		if (empty($managerGroups))
		{
			return [];
		}

		$ret = [];

		foreach ($managerGroups as $gid)
		{
			$uids = Access::getUsersByGroup($gid);
			array_walk($uids, function ($uid) use (&$ret) {
				try
				{
					$user = UserFetcher::getUser($uid);
				}
				catch (Exception $e)
				{
					return;
				}
				$ret[$user->email] = $user->name;
			});
		}

		return $ret;
	}

	/**
	 * Generates the difference between two recipient arrays. Each array follows the convention email => name
	 *
	 * @param   array  $allRecipients       The array to filter
	 * @param   array  $excludedRecipients  Recipients to remove from $allRecipients
	 *
	 * @return  array  The filtered array
	 * @since   1.0.0
	 */
	protected function getRecipientsArrayDiff(array $allRecipients, array $excludedRecipients): array
	{
		$nonRepliedToEmails = array_diff(array_keys($allRecipients), array_keys($excludedRecipients));

		return array_filter($allRecipients, function ($v, $k) use ($nonRepliedToEmails) {
			return in_array($k, $nonRepliedToEmails);
		}, ARRAY_FILTER_USE_BOTH);
	}

	/**
	 * Returns a list of email addresses which have been unsubscribed from comment notifications for the given Asset ID.
	 *
	 * @param   int  $asset_id  The asset ID for which email addresses have been unsubscribed.
	 *
	 * @return  string[]  A list of email addresses
	 * @since   1.0.0
	 */
	protected function getUnsubscribedEmails(int $asset_id): array
	{
		$db    = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->quoteName('email'))
			->from($db->quoteName('#__bbcomments_unsubscribe'))
			->where($db->quoteName('asset_id') . ' = :asset_id')
			->bind(':asset_id', $asset_id, ParameterType::INTEGER);

		return $db->setQuery($query)->loadColumn() ?? [];
	}

	/**
	 * Returns the Joomla user IDs given a list of email addresses
	 *
	 * @param   string[]  $emails  The emails to look for
	 *
	 * @return  array  A dictionary of email => ID for any emails that correspond to Joomla user accounts
	 * @since   1.0.0
	 */
	protected function getUserIDsByEmail(array $emails): array
	{
		// An empty recipient list must not generate invalid SQL (IN ()).
		if (!$emails)
		{
			return [];
		}

		$db     = $this->getDatabase();
		$emails = array_map([$db, 'q'], $emails);
		$query  = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->qn('email'),
				$db->qn('id'),
			])
			->from($db->qn('#__users'))
			->where($db->qn('email') . ' IN (' . implode(',', $emails) . ')');

		return $db->setQuery($query)->loadAssocList('email', 'id') ?? [];
	}

	/**
	 * Returns the emails and names of the people being replied to with a give comment
	 *
	 * @param   CommentTable  $comment
	 *
	 * @return  array  An dictionary following the convention email => name
	 * @since   1.0.0
	 */
	protected function getUserRecipients(CommentTable $comment): array
	{
		// Top level comments don't have reply-to users
		if (empty($comment->parent_id))
		{
			return [];
		}

		// Try to load the parent
		$parent = clone $comment;
		$parent->reset();

		if (!$parent->load($comment->parent_id))
		{
			return [];
		}

		$userEmails = [];

		$user                     = $this->getUser($parent);
		$userEmails[$user->email] = $user->name;

		return $userEmails;
	}

	/**
	 * Sends emails to a list of users about the given comment
	 *
	 * @param   string        $type               Email type. Must match the `key` column of the email templates table
	 * @param   CommentTable  $comment            The comment we are sending emails about
	 * @param   array         $recipients         A dictionary of email => name with the recipients
	 * @param   bool          $honorUnsubscribed  Should I honor unsubscribed emails? Default true.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function sendEmailMessages(string $type, CommentTable $comment, array $recipients, bool $honorUnsubscribed = true): void
	{
		// Do I have anything to do?
		if (empty($recipients))
		{
			return;
		}

		// Remove users who have already unsubscribed from this content
		if ($honorUnsubscribed)
		{
			$unsubscribedEmails = $this->getUnsubscribedEmails($comment->asset_id);
			$recipients         = array_filter($recipients, function ($v, $k) use ($unsubscribedEmails) {
				return !in_array($k, $unsubscribedEmails);
			}, ARRAY_FILTER_USE_BOTH);
		}

		if (empty($recipients))
		{
			return;
		}

		try
		{
			$mailer = clone
			(class_exists(MailerFactoryInterface::class)
				? Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer()
				: Factory::getMailer());

			$mailer->isHtml(true);
			$mailer->CharSet = 'UTF-8';
		}
		catch (Exception $e)
		{
			return;
		}

		// Populate email data
		$commentUser    = $this->getUser($comment);
		$meta           = Meta::getAssetAccessMeta($comment->asset_id);
		$publicUri      = Uri::getInstance($meta['public_url']);
		$returnUrl      = base64_encode($meta['public_url']);
		$protoUrl       = 'index.php?option=com_bbcomments&task=%s&returnurl=%s';
		$dateFormat     = Text::_('DATE_FORMAT_LC2');
		$processedComment   = HtmlFilter::filterText(HTMLHelper::_('bbcomments.processCommentTextForDisplay', $comment->body));
		$plainTextConverter = new Html2Text($processedComment);

		try
		{
			$avatarUrl = Avatar::getUserAvatar($commentUser->id, 48, $commentUser->email);
		}
		catch (Exception $e)
		{
			$avatarUrl = '';
		}

		$data = [
			'SITEURL'           => Uri::base(),
			'SITENAME'          => $this->getApplication()->get('sitename', 'A Joomla! site'),
			'NAME'              => strip_tags($commentUser->name),
			'EMAIL'             => strip_tags($commentUser->email),
			'IP'                => strip_tags($comment->ip),
			'USER_AGENT'        => strip_tags($comment->user_agent),
			'CONTENT_LINK'      => $meta['public_url'],
			'COMMENT_LINK'      => $publicUri->toString(),
			'CONTENT_TITLE'     => strip_tags($meta['title']),
			'CONTENT_CATEGORY'  => strip_tags($meta['category']),
			'AVATAR_URL'        => $avatarUrl,
			'COMMENT_SANITIZED' => $processedComment,
			'COMMENT_PLAINTEXT' => $plainTextConverter->getText(),
		];

		// Send emails
		$userIDs = $this->getUserIDsByEmail(array_keys($recipients));

		foreach ($recipients as $email => $name)
		{
			// Get the recipient Joomla user
			try
			{
				$recipient = isset($userIDs[$email]) ? UserFetcher::getUser($userIDs[$email]) : null;
			}
			catch (Exception $e)
			{
				$recipient = null;
			}

			if (empty($recipient))
			{
				$recipient        = new User();
				$recipient->name  = $name;
				$recipient->email = $email;
			}

			// Get the comment's URL
			$publicUri->setFragment('bbcomments-comment-' . $comment->getId());
			$publicUri->setVar('bbcomments_cid', $comment->getId());

			$returnUrlComment = base64_encode($publicUri->toString());

			// Get the localised “created on” date
			try
			{
				$tz = new DateTimeZone($recipient->getParam('timezone', $this->getApplication()->get('offset', 'UTC')));
			}
			catch (Exception $e)
			{
				$tz = new DateTimeZone('UTC');
			}

			$jCreatedOn = Factory::getDate($comment->created);
			$jCreatedOn->setTimezone($tz);

			// Try to send an email
			try
			{
				// Valid for one year, independently of 24-hour moderator-action links.
				$unsubscribeUrl = SignedURL::getAbsoluteSignedURL(
					sprintf($protoUrl, 'comments.unsubscribe', urlencode($returnUrl)),
					$comment,
					$recipient->email,
					365 * 24 * 60 * 60
				);
				$sent = TemplateEmails::sendMail($type, array_merge($data, [
					'RECIPIENT_NAME'   => strip_tags($recipient->name),
					'RECIPIENT_EMAIL'  => strip_tags($recipient->email),
					'DATE_LOCAL'       => $jCreatedOn->format($dateFormat, true),
					'PUBLISH_URL'      => SignedURL::getAbsoluteSignedURL(sprintf($protoUrl, 'comments.publish', urlencode($returnUrlComment)), $comment, $recipient->email),
					'UNPUBLISH_URL'    => SignedURL::getAbsoluteSignedURL(sprintf($protoUrl, 'comments.unpublish', urlencode($returnUrl)), $comment, $recipient->email),
					'DELETE_URL'       => SignedURL::getAbsoluteSignedURL(sprintf($protoUrl, 'comments.delete', urlencode($returnUrl)), $comment, $recipient->email),
					'POSSIBLESPAM_URL' => SignedURL::getAbsoluteSignedURL(sprintf($protoUrl, 'comments.possiblespam', urlencode($returnUrl)), $comment, $recipient->email),
					'SPAM_URL'         => SignedURL::getAbsoluteSignedURL(sprintf($protoUrl, 'comments.reportspam', urlencode($returnUrl)), $comment, $recipient->email),
					'UNSPAM_URL'       => SignedURL::getAbsoluteSignedURL(sprintf($protoUrl, 'comments.reportham', urlencode($returnUrlComment)), $comment, $recipient->email),
					'UNSUBSCRIBE_URL'  => $unsubscribeUrl,
					// The HTML-only form is escaped for use in href attributes; the
					// ordinary form above is a raw URL for text/plain mail templates.
					'UNSUBSCRIBE_URL_HTML' => htmlspecialchars($unsubscribeUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
				]), $recipient);
				if (!$sent)
				{
					// Avoid logging personal data or comment bodies.
					try { Log::add('BlackBrix Comments: E-Mail-Vorlage ' . $type . ' konnte nicht versendet werden. Vorlagen und Mail-Konfiguration pruefen.', Log::WARNING, 'com_bbcomments'); } catch (\Throwable $ignored) {}
				}
			}
			catch (\Throwable $e)
			{
				try { Log::add('BlackBrix Comments: Ausnahme beim E-Mail-Versand fuer Vorlage ' . $type . ' (' . get_class($e) . ').', Log::WARNING, 'com_bbcomments'); } catch (\Throwable $ignored) {}
				continue;
			}
		}
	}

	/**
	 * Get a user object from a comment object
	 *
	 * @param   CommentTable  $comment
	 *
	 * @return  User|null
	 * @since   3.0.0
	 */
	private function getUser(CommentTable $comment): ?User
	{
		if ($comment->created_by)
		{
			try
			{
				return UserFetcher::getUser($comment->created_by);
			}
			catch (Exception $e)
			{
				return null;
			}
		}

		$user        = new User(0);
		$user->name  = $comment->name;
		$user->email = $comment->email;

		return $user;
	}
}
