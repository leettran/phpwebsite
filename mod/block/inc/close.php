<?php

/**
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @version $Id$
 */

Block::show();

if (Current_User::allow('block')) {
    $key = Core\Key::getCurrent();
    if (Core\Key::checkKey($key) && javascriptEnabled()) {
        $val['address'] = sprintf('index.php?module=block&action=js_block_edit&key_id=%s&authkey=%s',
        $key->id, Current_User::getAuthkey());
        $val['label'] = dgettext('block', 'Add block here');
        $val['width'] = 640;
        $val['height'] = 480;
        MiniAdmin::add('block', javascript('open_window', $val));
    }
}

?>