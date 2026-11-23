<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use BlackBrix\Component\Bbcomments\Administrator\Model\CommentsModel;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Featured Article comments summary
 *
 * This layout is used when displaying the comments summary in the featured article view.
 *
 * Override by creating the folder templates/YOUR_TEMPLATE/html/layouts/blackbrix/bbcomments/content and copying this file in
 * there. That file will be used instead of the file plugins/content/bbcomments/layouts/blackbrix/bbcomments/content/featured.php
 *
 * @var  array          $displayData The incoming display data. It's extracted into scope in the following variables.
 * @var  CMSApplication $app         The current application
 * @var  CommentsModel  $model       The BlackBrix Comments Comments model object
 * @var  stdClass       $row         The Joomla article object.
 * @var  array          $meta        Display metadata, returned by Meta::getAssetAccessMeta().
 *
 * @see  \BlackBrix\Component\Bbcomments\Site\Helper\Meta::getAssetAccessMeta()
 */
extract($displayData);

// Get the number of comments for this article
$model->setState('filter.asset_id', $row->asset_id);
$model->setState('filter.enabled', 1);
$numComments = (int) $model->getTotal();

// The resolved article/category setting is passed by the content plugin.
$isGuestbook = isset($meta['parameters'])
    && $meta['parameters'] instanceof \Joomla\Registry\Registry
    && $meta['parameters']->get('comments_context', 'comment') === 'guestbook';

// Language key to use
$headerKey = 'COM_BBCOMMENTS_COMMENTS_HEADER_N_COMMENTS';
$key       = sprintf('COM_BBCOMMENTS_COMMENTS_%s_HEADER_N_COMMENTS', strtoupper($meta['type']));
$lang      = $app->getLanguage();
$headerKey = $isGuestbook ? 'COM_BBCOMMENTS_GUESTBOOK_HEADER_N_ENTRIES' : ($lang->hasKey($key) ? $key : $headerKey);

$uri = Uri::getInstance($meta['public_url']);
$uri->setFragment('bbcomments-comments-section');
?>
<aside class="bbcomments-comments-counter--featured">
	<a href="<?= $uri->toString() ?>">
		<data itemprop="commentCount" value="<?= $numComments ?>">
			<?php if ($numComments === 0): ?>
                <span class="icon-comments" aria-hidden="true"></span> <?= Text::_($isGuestbook ? 'COM_BBCOMMENTS_GUESTBOOK_ACTION' : 'COM_BBCOMMENTS_COMMENTS_WRITE_ACTION') ?>
            <?php else: ?>
                <?= Text::plural($headerKey, $numComments, $row->title) ?>
            <?php endif; ?>
		</data>
	</a>
</aside>
