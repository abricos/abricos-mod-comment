<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

/**
 * Class CommentManager
 *
 * @property CommentModule $module
 */
class CommentManager extends Ab_ModuleManager {

    public function __construct(CommentModule $module){
        parent::__construct($module);

        $modURating = Abricos::GetModule("urating");
        CommentManager::$isURating = !empty($modURating);
    }

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


    public function FullList($page, $limit){
        if (!$this->IsAdminRole()){
            return null;
        }
        return CommentQuery::FullList($this->db, $page, $limit);
    }

    public function FullListCount(){
        if (!$this->IsAdminRole()){
            return null;
        }
        return CommentQuery::FullListCount($this->db);
    }

    public function ChangeStatus($commentId, $newStatus){
        if (!$this->IsAdminRole()){
            return null;
        }
        CommentQuery::SpamSet($this->db, $commentId, $newStatus);
    }


    private function ContentManager($contentid){
        $cinfo = Ab_CoreQuery::ContentInfo($this->db, $contentid);
        if (empty($cinfo)){
            return null;
        }
        $module = Abricos::GetModule($cinfo['modman']);
        $manager = $module->GetManager();
        return $manager;
    }


    public function Append($contentid, $parentCommentId, $text){

        if (empty($text)){
            return null;
        }
        if (!$this->IsWriteRole()){
            return null;
        }

        $manager = $this->ContentManager($contentid);
        if (is_null($manager)){
            return null;
        }

        // разрешает ли управляющий менеджер запись комментария
        if (method_exists($manager, 'Comment_IsWrite')){
            if (!$manager->Comment_IsWrite($contentid)){
                return null;
            }
        } else if (method_exists($manager, 'IsCommentAppend')){ // TODO: метод для поддрежки, подлежит удалению
            if (!$manager->IsCommentAppend($contentid)){
                return null;
            }
        } else {
            // нет проверочного метода, значит добавить комментарий нельзя
            return null;
        }

        $utmanager = Abricos::TextParser();

        $text = $utmanager->Parser($text);
        if (empty($text)){
            return null;
        }
        $d = new stdClass();
        $d->pid = $parentCommentId;
        $d->bd = $text;
        $d->uid = $this->userid;
        $d->id = CommentQuery::Append($this->db, $contentid, $d);
        $d->cid = $contentid;

        // управляюищй менеджер отправит уведомление
        if (method_exists($manager, 'Comment_SendNotify')){
            $manager->Comment_SendNotify($d);
        } else if (method_exists($manager, 'CommentSendNotify')){ // TODO: метод для поддрежки, подлежит удалению
            $manager->CommentSendNotify($d);
        }
    }

    public function Preview($text){
        $ret = new stdClass();
        if (!$this->IsWriteRole()){
            $ret->text = "Access denied!";
        } else {
            $utmanager = Abricos::TextParser();
            $ret->text = $utmanager->Parser($text);
        }
        return $ret;
    }

    private $_maxCommentId = 0;


    public function Comments($contentid, $lastid = 0, $parentCommentId = 0, $newComment = '', $retarray = false){
        if (!$this->IsViewRole()){
            return null;
        }
        $manager = $this->ContentManager($contentid);

        if (is_null($manager)){
            return null;
        }

        // разрешает ли управляющий менеджер получить список комментариев
        if (method_exists($manager, 'Comment_IsViewList')){
            if (!$manager->Comment_IsViewList($contentid)){
                return null;
            }
        } else if (method_exists($manager, 'IsCommentList')){ // TODO: метод для поддрежки, подлежит удалению
            if (!$manager->IsCommentList($contentid)){
                return null;
            }
        } else {
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
        if (!$this->IsViewRole()){
            return null;
        }

        $ret = new stdClass();
        $ret->list = $this->Comments($contentid, $lastid, $parentCommentId, $newComment, true);
        $ret->lastview = -1;
        if (empty($this->userid) || empty($ret->list)){
            return $ret;
        }

        $lv = CommentQuery::LastView($this->db, $this->userid, $contentid);
        if (empty($lv)){
            CommentQuery::LastViewAppend($this->db, $this->userid, $contentid, $this->_maxCommentId);
        } else {
            $ret->lastview = $lv['id'];
            CommentQuery::LastViewUpdate($this->db, $this->userid, $contentid, $this->_maxCommentId);
        }

        return $ret;
    }
    /**/

    /**
     * Можно ли проголосовать текущему пользователю за комментарий
     *
     * Метод вызывается из модуля URating
     *
     * Возвращает код ошибки:
     *  0 - все нормально, голосовать можно,
     *  2 - голосовать можно только с положительным рейтингом,
     *  3 - недостаточно голосов (закончились голоса),
     *  4 - нельзя голосовать за свой комментарий,
     *  5 - закончился период для голосования
     *
     * @param URatingUserReputation $uRep
     * @param string $act
     * @param integer $userid
     * @param string $eltype
     */
    public function URating_IsElementVoting(URatingUserReputation $uRep, $act, $elid, $eltype){
        if ($eltype != ''){
            return 99;
        }

        $info = CommentQuery::CommentInfo($this->db, $elid);
        if (empty($info)){
            return 99;
        }

        if ($info['uid'] == $this->user->id){
            return 4;
        }

        $module = Abricos::GetModule($info['m']);
        if (empty($module)){
            return 99;
        }

        $manager = $module->GetManager();
        if (empty($manager)){
            return 99;
        }

        if ($this->IsAdminRole()){ // админу можно голосовать всегда
            return 0;
        }

        if ($uRep->reputation < 1){ // голосовать можно только с положительным рейтингом
            return 2;
        }

        $votes = URatingManager::$instance->UserVoteCountByDay();

        // кол-во голосов за комментарий = кол-ву репутации умноженной на 2
        $voteRepCount = intval($votes['comment']);
        if ($uRep->reputation * 2 <= $voteRepCount){
            return 3;
        }

        // разрешает ли управляющий менеджер голосовать за комментарий
        if (!method_exists($manager, 'Comment_IsVoting')){
            return 99;
        }

        return $manager->Comment_IsVoting($uRep, $act, $elid, $info['ctid']);
    }
}

?>