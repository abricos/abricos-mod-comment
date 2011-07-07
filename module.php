<?php
/**
 * Модуль "Комментарии"
 * 
 * @version $Id$
 * @package Abricos
 * @subpackage Comment
 * @copyright Copyright (C) 2008 Abricos All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

/**
 * Модуль "Комментарии"
 * @package Abricos
 * @subpackage Comment
 */
class CommentModule extends CMSModule{
	
	private $_manager = null;
	
	function __construct(){
		$this->version = "0.3.2";
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

class CommentPermission extends CMSPermission {
	
	public function CommentPermission(CommentModule $module){
		$defRoles = array(
			new CMSRole(CommentAction::VIEW, 1, User::UG_GUEST),
			new CMSRole(CommentAction::VIEW, 1, User::UG_REGISTERED),
			new CMSRole(CommentAction::VIEW, 1, User::UG_ADMIN),

			new CMSRole(CommentAction::WRITE, 1, User::UG_REGISTERED),
			new CMSRole(CommentAction::WRITE, 1, User::UG_ADMIN),
			
			new CMSRole(CommentAction::ADMIN, 1, User::UG_ADMIN)
		);
		parent::CMSPermission($module, $defRoles);
	}
	
	public function GetRoles(){
		return array(
			CommentAction::VIEW => $this->CheckAction(CommentAction::VIEW),
			CommentAction::WRITE => $this->CheckAction(CommentAction::WRITE),
			CommentAction::ADMIN => $this->CheckAction(CommentAction::ADMIN) 
		);
	}
}

$modComment = new CommentModule();
CMSRegistry::$instance->modules->Register($modComment);

?>