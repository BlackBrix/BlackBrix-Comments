<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/**
 * View Template for the submitting comments.
 *
 * This is called by default.php
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var \BlackBrix\Component\Bbcomments\Site\View\Comments\HtmlView $this */

$cParams = ComponentHelper::getParams('com_bbcomments');
$badUx   = ($cParams->get('comments_reply_bad_ux', 0) == 1) && empty($this->form->getValue('body'));

HTMLHelper::_('behavior.formvalidator');
?>
<?php if ($badUx && (!$this->isGuestbook || $this->publishedGuestbookEntryCount > 0 || !$this->hasGuestbookBodyButton)): ?>
	<div class="bbcomments-comment-hider" id="bbcomments-comment-hider">
		<button type="button"
				id="bbcomments-comment-hider-button"
				class="bbcomments-comment-link">
			<span class="icon-comments" aria-hidden="true"></span>
            <?= Text::_(!empty($this->isGuestbook) ? 'COM_BBCOMMENTS_GUESTBOOK_ACTION' : 'COM_BBCOMMENTS_COMMENTS_FORM_HEADER'); ?>
		</button>
	</div>
<?php endif; ?>

<form action="<?= Route::_('index.php?option=com_bbcomments&task=comment.save') ?>"
	method="post" name="bbcomments-comment-form" id="bbcommentsCommentForm"
	class="form-validate <?= $badUx ? 'd-none' : ''; ?>"
	style="<?= $badUx ? 'display: none;' : ''; ?>"
	aria-label="<?= Text::_('COM_BBCOMMENTS_COMMENTS_FORM_ARIA_LABEL', true) ?>"
>
	<input type="hidden" name="returnurl" value="<?= base64_encode(Uri::getInstance()->toString(['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'])) ?>">
	<input type="hidden" name="view" value="">
	<input type="hidden" name="id" value="">
	<?= HTMLHelper::_('form.token') ?>

	<div class="mt-3 pt-2 mb-2 border-top border-2 border-dark">
		<h3 class="h1 my-3">
			<?= Text::_(!empty($this->isGuestbook) ? 'COM_BBCOMMENTS_GUESTBOOK_ACTION' : 'COM_BBCOMMENTS_COMMENTS_FORM_HEADER') ?>
		</h3>

		<?= $this->loadPosition('bbcomments-before-reply'); ?>

		<div id="bbcomments-comment-inreplyto-wrapper" class="alert alert-info d-none">
			<div class="d-flex flex-wrap">
				<div class="flex-grow-1">
					<?= Text::_('COM_BBCOMMENTS_COMMENTS_FORM_INREPLYTO_LABEL'); ?>
					<span id="bbcomments-comment-inreplyto-name" class="text-secondary fw-bold">Some User</span>
				</div>

				<button id="bbcomments-comment-inreplyto-cancel"
						type="button"
						class="ms-2 btn btn-sm btn-outline-danger"
				><?= Text::_('COM_BBCOMMENTS_COMMENTS_FORM_CANCELREPLY'); ?></button>
			</div>
		</div>

		<?php foreach (array_keys($this->form->getFieldsets()) as $fieldSet)
		{
			echo $this->form->renderFieldset($fieldSet);
		} ?>

		<div class="control-group">
			<div class="controls">
				<button type="submit"
						class="btn btn-lg btn-primary w-100 bbcomments-comment-submit-btn"
				>
					<span class="fa fa-comment-dots" aria-hidden="true"></span>
					<?= Text::_('COM_BBCOMMENTS_COMMENTS_FORM_EDIT_BTN_SUBMIT') ?>
				</button>
			</div>
		</div>

		<?= $this->loadPosition('bbcomments-after-reply'); ?>
	</div>
</form>