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
        $this->version = "0.4.3";
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
    const ADMIN = 50;
}

class CommentPermission extends Ab_UserPermission {

    public function __construct(CommentModule $module){
        parent::__construct($module, array(
            new Ab_UserRole(CommentAction::ADMIN, Ab_UserGroup::ADMIN),
        ));
    }

    public function GetRoles(){
        return array(
            CommentAction::ADMIN => $this->CheckAction(CommentAction::ADMIN)
        );
    }
}

Abricos::ModuleRegister(new CommentModule());
