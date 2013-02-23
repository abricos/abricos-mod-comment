<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Bopros
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

require_once 'dbquery.php';

class CommentManager extends Ab_ModuleManager {

	/**
	 * 
	 * @var CommentModule
	 */
	public $module = null;
	
	public function __construct(CommentModule $module){
		parent::__construct($module);
	}
	
	public function IsAdminRole(){
		return $this->IsRoleEnable(CommentAction::ADMIN);
	}
	
	public function IsWriteRole(){
		return $this->IsRoleEnable(CommentAction::WRITE);
	}
	
	public function IsViewRole(){
		return $this->IsRoleEnable(CommentAction::VIEW);
	}
	
	public function DSProcess($name, $rows){
		$p = $rows->p;
		switch ($name){
			case 'fulllist':
				foreach ($rows->r as $r){
					if ($r->f == 'u'){
						if ($r->d->act == 'status'){
							$this->ChangeStatus($r->d->id, $r->d->st);
						} 
					}
				}
				break;
		}
	}
	
	public function DSGetData($name, $rows){
		$p = $rows->p;
		switch ($name){
			case 'fulllist': return $this->FullList($p->page, $p->limit);
			case 'fulllistcount': return $this->FullListCount();
		}
		return null;
	}
	
	public function AJAX($d){
		switch($d->do){
			case 'preview': 
				return $this->Preview($d->text);
			case 'list': 
				return $this->CommentsWithLastView($d->cid, $d->lid, $d->pid, $d->text);
		}
		return null;
	}
	/**
	 * Вернуть указатель на полный список комментариев.
	 * 
	 * @param Integer $page
	 * @param Integer $limit
	 * @return Integer
	 */
	public function FullList($page, $limit){
		if (!$this->IsAdminRole()){ return null; }
		return CommentQuery::FullList($this->db, $page, $limit);
	}

	public function FullListCount(){
		if (!$this->IsAdminRole()){ return null; }
		return CommentQuery::FullListCount($this->db);
	}

	public function ChangeStatus($commentId, $newStatus){
		if (!$this->IsAdminRole()){ return null; }
		CommentQuery::SpamSet($this->db, $commentId, $newStatus);
	}
	
	/**
	 * Получить менеджер, управляющий списком комментариев по идентификатору контента
	 * @param integer $contentid идентификатор контента
	 */
	private function ContentManager($contentid){
		$cinfo = Ab_CoreQuery::ContentInfo($this->db, $contentid);
		if (empty($cinfo)){ return null; }
		$module = Abricos::GetModule($cinfo['modman']);
		$manager = $module->GetManager();
		return $manager;
	}
	
	/**
	 * Добавить комментарий
	 * 
	 * @param integer $contentid идентификатор страницы
	 * @param object $d данные комментария
	 */
	public function Append($contentid, $parentCommentId, $text){
		if (empty($text)){ return null; }
		if (!$this->IsWriteRole()){ return null; }

		$manager = $this->ContentManager($contentid);
		if (is_null($manager)){ return null; }
		
		// разрешает ли управляющий менеджер запись комментария
		if (method_exists($manager, 'Comment_IsWrite')){
			if (!$manager->Comment_IsWrite($contentid)){ return null; }
		}else if (method_exists($manager, 'IsCommentAppend')){ // TODO: метод для поддрежки, подлежит удалению
			if (!$manager->IsCommentAppend($contentid)){ return null; }
		}else{
			// нет проверочного метода, значит добавить комментарий нельзя 
			return null;
		}

		$utmanager = Abricos::TextParser();
		
		$text = $utmanager->Parser($text);
		if (empty($text)){ return null; }
		$d = new stdClass();
		$d->pid = $parentCommentId;
		$d->bd = $text;
		$d->uid = $this->userid; 
		$d->id = CommentQuery::Append($this->db, $contentid, $d);
		$d->cid = $contentid;
		
		// управляюищй менеджер отправит уведомление
		if (method_exists($manager, 'Comment_SendNotify')){
			$manager->Comment_SendNotify($d);
		}else if (method_exists($manager, 'Comment_SendNotify')){ // TODO: метод для поддрежки, подлежит удалению
			$manager->Comment_SendNotify($d);
		}
	}
	
	public function Preview($text){
		$ret = new stdClass();
		if (!$this->IsWriteRole()){
			$ret->text = "Access denied!";
		}else{
			$utmanager = Abricos::TextParser();
			$ret->text = $utmanager->Parser($text);
		}
		return $ret;
	}
	
	private $_maxCommentId = 0;
	
	/**
	 * Получить список комментариев
	 * @param integer $contentId идентификатор контента
	 * @param integer $lastid последний передаваемый идентификатор (для подзагрузки новых)
	 */
	public function Comments($contentid, $lastid = 0, $parentCommentId = 0, $newComment = '', $retarray = false){
		if (!$this->IsViewRole()){ return null; }
		$manager = $this->ContentManager($contentid);
		
		if (is_null($manager)){ return null; }
		
		// разрешает ли управляющий менеджер получить список комментариев
		if (method_exists($manager, 'Comment_IsViewList')){
			if (!$manager->Comment_IsViewList($contentid)){
				return null;
			}
		}else if (method_exists($manager, 'IsCommentList')){ // TODO: метод для поддрежки, подлежит удалению
			if (!$manager->IsCommentList($contentid)){
				return null;
			}
		}else{
			// нет проверочного метода, значит добавить комментарий нельзя
			return null;
		}
		
		if (!empty($newComment)){
			$this->Append($contentid, $parentCommentId, $newComment);
		}
		
		$rows = CommentQuery::Comments($this->db, $contentid, $lastid);
		if (!$retarray){
			return $rows; 
		}
		$list = array();
		$max = 0;
		while (($row = $this->db->fetch_array($rows))){
			$list[$row['id']] = $row;
			$max = max($max, $row['id']);
		}
		$this->_maxCommentId = $max; 
		return $list;
	}
	
	public function CommentsWithLastView($contentid, $lastid = 0, $parentCommentId = 0, $newComment = ''){
		$ret = new stdClass();
		$ret->list = $this->Comments($contentid, $lastid, $parentCommentId, $newComment, true);
		$ret->lastview = -1;
		if (empty($this->userid)){ return $ret; }
		
		$lv = CommentQuery::LastView($this->db, $this->userid, $contentid);
		if (empty($lv)){
			CommentQuery::LastViewAppend($this->db, $this->userid, $contentid, $this->_maxCommentId);
		}else{
			$ret->lastview = $lv['id'];
			 CommentQuery::LastViewUpdate($this->db, $this->userid, $contentid, $this->_maxCommentId);
		}
		 
		return $ret;
	}
}

?>