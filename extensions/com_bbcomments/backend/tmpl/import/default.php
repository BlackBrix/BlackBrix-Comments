<?php
/** BlackBrix Comments import admin template. Copyright (c) 2026 BlackBrix. GPLv3+. */
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$p = $this->preview;
?>
<div class="container-fluid">
    <p><a href="<?= Route::_('index.php?option=com_bbcomments&view=comments') ?>" class="btn btn-secondary"><?= Text::_('COM_BBCOMMENTS_IMPORT_BACK') ?></a></p>
    <div class="alert alert-info"><?= Text::_('COM_BBCOMMENTS_IMPORT_WARNING') ?></div>
    <form action="<?= Route::_('index.php?option=com_bbcomments&task=import.preview') ?>" method="post" enctype="multipart/form-data">
        <label for="bbcomments-import-file" class="form-label"><?= Text::_('COM_BBCOMMENTS_IMPORT_SELECT') ?></label>
        <input class="form-control" type="file" name="import_file" id="bbcomments-import-file" accept=".json,application/json" required>
        <p class="form-text"><?= Text::_('COM_BBCOMMENTS_IMPORT_LIMIT') ?></p>
        <button type="submit" class="btn btn-primary"><?= Text::_('COM_BBCOMMENTS_IMPORT_PREVIEW') ?></button>
        <?= HTMLHelper::_('form.token') ?>
    </form>
    <?php if (!empty($p['sha256'])) : ?>
        <hr>
        <h3><?= Text::_('COM_BBCOMMENTS_IMPORT_PREVIEW_TITLE') ?></h3>
        <dl class="row">
            <dt class="col-sm-4"><?= Text::_('COM_BBCOMMENTS_IMPORT_COMMENTS') ?></dt><dd class="col-sm-8"><?= (int) $p['count'] ?></dd>
            <dt class="col-sm-4"><?= Text::_('COM_BBCOMMENTS_IMPORT_UNSUBSCRIBES') ?></dt><dd class="col-sm-8"><?= (int) ($p['unsubscribe_count'] ?? 0) ?></dd>
            <dt class="col-sm-4"><?= Text::_('COM_BBCOMMENTS_IMPORT_ARTICLES') ?></dt><dd class="col-sm-8"><?= (int) $p['articles'] ?></dd>
            <dt class="col-sm-4"><?= Text::_('COM_BBCOMMENTS_IMPORT_HTML') ?></dt><dd class="col-sm-8"><?= (int) $p['changed_html'] ?></dd>
            <dt class="col-sm-4"><?= Text::_('COM_BBCOMMENTS_IMPORT_USERS') ?></dt><dd class="col-sm-8"><?= (int) $p['users_missing'] ?></dd>
        </dl>
        <div class="alert alert-warning"><?= Text::_('COM_BBCOMMENTS_IMPORT_CONFIRM') ?></div>
        <div class="d-flex gap-2">
            <form action="<?= Route::_('index.php?option=com_bbcomments&task=import.runimport') ?>" method="post">
                <button type="submit" class="btn btn-success"><?= Text::_('COM_BBCOMMENTS_IMPORT_EXECUTE') ?></button>
                <?= HTMLHelper::_('form.token') ?>
            </form>
            <form action="<?= Route::_('index.php?option=com_bbcomments&task=import.cancel') ?>" method="post">
                <button type="submit" class="btn btn-secondary"><?= Text::_('JCANCEL') ?></button>
                <?= HTMLHelper::_('form.token') ?>
            </form>
        </div>
    <?php endif; ?>
</div>
