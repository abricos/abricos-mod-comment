<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Модуль "Комментарии"
 *
 * @package Abricos
 * @subpackage Comment
 */
class CommentModule extends Ab_Module {

    private $_manager = null;

    function __construct(){
        $this->version = "0.4.2";
        $this->name = "comment";

        $this->permission = new CommentPermission($this);
    }

    /**
     * Получить менеджер
     *
     * @return CommentManager
     */
    public function GetManager(){
        if (is_null($this->_manager)){
            require_once 'includes/manager.php';
            $this->_manager = new CommentManager($this);
        }
        return $this->_manager;
    }
}

class CommentAction {
    const VIEW = 10;
    const WRITE = 20;
    const ADMIN = 50;
}

class CommentPermission extends Ab_UserPermission {

    public function CommentPermission(CommentModule $module){

        $defRoles = array(
            new Ab_UserRole(CommentAction::VIEW, Ab_UserGroup::GUEST),
            new Ab_UserRole(CommentAction::VIEW, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(CommentAction::VIEW, Ab_UserGroup::ADMIN),

            new Ab_UserRole(CommentAction::WRITE, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(CommentAction::WRITE, Ab_UserGroup::ADMIN),

            new Ab_UserRole(CommentAction::ADMIN, Ab_UserGroup::ADMIN),
        );

        parent::__construct($module, $defRoles);
    }

    public function GetRoles(){
        return array(
            CommentAction::VIEW => $this->CheckAction(CommentAction::VIEW),
            CommentAction::WRITE => $this->CheckAction(CommentAction::WRITE),
            CommentAction::ADMIN => $this->CheckAction(CommentAction::ADMIN)
        );
    }
}

Abricos::ModuleRegister(new CommentModule());

?>