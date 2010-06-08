<?php
/**
 * vmail - phpwebsite module
 *
 * See docs/AUTHORS and docs/COPYRIGHT for relevant info.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @version $Id$
 * @author Verdon Vaillancourt <verdonv at gmail dot com>
 */


class vMail_Runtime
{

    public static function showBlock() {
        if (Core\Settings::get('vmail', 'enable_sidebox')) {
            if (Core\Settings::get('vmail', 'sidebox_homeonly')) {
                $key = Core\Key::getCurrent();
                if (!empty($key) && $key->isHomeKey()) {
                    vMail_Runtime::showvMailBlock();
                }
            } else {
                vMail_Runtime::showvMailBlock();
            }
        }
    }

    public function showvMailBlock() {

        $db = new Core\DB('vmail_recipients');
        $db->addColumn('id');
        $db->addColumn('label');
        $db->addWhere('active', 1);
        $db->addOrder('label asc');
        $result = $db->select();

        if (!Core\Error::logIfError($result) && !empty($result)) {
            foreach ($result as $recipient) {
                $choices[$recipient['id']] = $recipient['label'];
            }
            $form = new Core\Form('vMail_recipients');
            $form->addHidden('module', 'vmail');
            $form->addHidden('uop', 'view_recipient');
            $form->addSelect('recipient', $choices);
            $form->setLabel('recipient', dgettext('vmail', 'Available recipients'));
            $form->addSubmit('submit', dgettext('vmail', 'Contact'));
            $tpl = $form->getTemplate();

            $tpl['TITLE'] = Core\Text::parseOutput(Core\Settings::get('vmail', 'module_title'));
            $tpl['TEXT'] = Core\Text::parseOutput(Core\Settings::get('vmail', 'sidebox_text'));

            Core\Core::initModClass('layout', 'Layout.php');
            Layout::add(Core\Template::process($tpl, 'vmail', 'block.tpl'), 'vmail', 'vmail_sidebox');
        }
    }


}

?>