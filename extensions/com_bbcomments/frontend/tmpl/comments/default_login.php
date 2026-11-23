<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

// The return URL is the current article. Joomla verifies this on login.
$returnUrl = base64_encode(Uri::getInstance()->toString());
$loginUrl = Route::_('index.php?option=com_users&view=login&return=' . rawurlencode($returnUrl));
?>
<div class="bbcomments-login-note" role="note">
    <p><?= Text::_(!empty($this->isGuestbook) ? 'COM_BBCOMMENTS_LOGIN_REQUIRED_GUESTBOOK' : 'COM_BBCOMMENTS_LOGIN_REQUIRED') ?>
    <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>"><?= Text::_('COM_BBCOMMENTS_LOGIN_LINK') ?></a></p>
</div>
