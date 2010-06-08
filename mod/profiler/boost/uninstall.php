<?php

/**
 * Uninstall file for profiles
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @version $Id$
 */

function profiler_uninstall(&$content)
{

    Core\DB::dropTable('profiles');
    Core\DB::dropTable('profiler_division');
    $content[] = dgettext('profiler', 'Profiles table removed.');

    return TRUE;
}

?>
