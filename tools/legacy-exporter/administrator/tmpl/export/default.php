<?php
/** Copyright (c) 2026 BlackBrix. GPL-3.0-or-later. */
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="container-fluid">
    <div class="alert alert-warning"><?= Text::_('COM_BBENGAGEEXPORT_WARNING') ?></div>
    <?php if ($this->error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($this->error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php else: ?>
        <p><?= Text::sprintf('COM_BBENGAGEEXPORT_COUNT', $this->count) ?></p>
        <p><?= Text::_('COM_BBENGAGEEXPORT_DETAILS') ?></p>
        <form method="post" action="<?= Route::_('index.php?option=com_bbengageexport&task=export.download') ?>">
            <button class="btn btn-primary" type="submit" <?= $this->count < 1 ? 'disabled' : '' ?>><?= Text::_('COM_BBENGAGEEXPORT_DOWNLOAD') ?></button>
            <?= HTMLHelper::_('form.token') ?>
        </form>
    <?php endif; ?>
</div>
