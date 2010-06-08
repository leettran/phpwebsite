<?php

/**
 * Removes version tables for uninstalled modules.
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @version $Id$
 */

function version_unregister($module, &$content)
{
    $install_file = PHPWS_SOURCE_DIR . 'mod/' . $module . '/boost/install.sql';

    if (!is_file($install_file)) {
        return;
    }

    $install_sql = file($install_file);

    if (empty($install_file)) {
        return;
    }

    foreach ($install_sql as $sql) {
        if (!preg_match('/^create /i', $sql)) {
            continue;
        }

        $table_name = Core\DB::extractTableName($sql);

        if (empty($table_name)) {
            continue;
        }

        $version_table = $table_name . '_version';
        $version_table_seq = $version_table . '_seq';

        if (!Core\DB::isTable($version_table)) {
            continue;
        }

        $result = Core\DB::dropTable($version_table);
        if (Core\Error::isError($result)) {
            Core\Error::log($result);
            $content[] = dgettext('version', 'There was an error removing a version table.');
        } else {
            $content[] = sprintf(dgettext('version', 'Version table removed: %s'), $version_table);
        }
    }
}

?>