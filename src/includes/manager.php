<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class CommentManager
 *
 * @property CommentModule $module
 */
class CommentManager extends Ab_ModuleManager {

    public function IsAdminRole(){
        return $this->IsRoleEnable(CommentAction::ADMIN);
    }

    public function IsWriteRole(){
        if ($this->IsAdminRole()){
            return true;
        }
        return $this->IsRoleEnable(CommentAction::WRITE);
    }

    public function IsViewRole(){
        if ($this->IsWriteRole()){
            return true;
        }
        return $this->IsRoleEnable(CommentAction::VIEW);
    }

    public function AJAX($d){
        return $this->GetApp()->AJAX($d);
    }
}
