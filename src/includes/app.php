<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';
require_once 'models.php';

/**
 * Class CommentApp
 *
 * @property CommentManager $manager
 */
class CommentApp extends AbricosApplication {

    protected function GetClasses(){
        return array(
            'Owner' => 'CommentOwner',
            'Statistic' => 'CommentStatistic',
            'StatisticList' => 'CommentStatisticList',
            'Comment' => 'Comment',
            'CommentList' => 'CommentList'
        );
    }

    protected function GetStructures(){
        return 'Owner,Statistic,Comment';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'reply':
                return $this->ReplyToJSON($d->owner, $d->reply);
            case 'replyPreview':
                return $this->ReplyPreviewToJSON($d->owner, $d->reply);
            case 'commentList':
                return $this->CommentListToJSON($d->owner);
        }
        return null;
    }

    public function IsRaiting(){
        $modURating = Abricos::GetModule("urating");
        return !empty($modURating);
    }

    private $_cache = array();

    public function CacheClean(){
        $this->_cache = array();
    }

    /**
     * @param $owner
     * @return CommentOwner
     */
    public function OwnerNormalize($owner){
        if ($owner instanceof CommentOwner){
            return $owner;
        }

        return $this->InstanceClass('Owner', $owner);
    }

    private function GetOwnerApp($moduleName){
        if (!isset($this->_cache['app'])){
            $this->_cache['app'] = array();
        }
        if (isset($this->_cache['app'][$moduleName])){
            return $this->_cache['app'][$moduleName];
        }
        $module = Abricos::GetModule($moduleName);
        if (empty($module)){
            return null;
        }
        $manager = $module->GetManager();
        if (empty($manager)){
            return null;
        }
        if (!method_exists($manager, 'GetApp')){
            return null;
        }
        return $this->_cache['app'][$moduleName] = $manager->GetApp();
    }

    private function OwnerAppFunctionExist($module, $fn){
        $ownerApp = $this->GetOwnerApp($module);
        if (empty($ownerApp)){
            return false;
        }
        if (!method_exists($ownerApp, $fn)){
            return false;
        }
        return true;
    }

    public function IsCommentView(CommentOwner $owner){
        $owner = $this->OwnerNormalize($owner);
        if (!$this->OwnerAppFunctionExist($owner->module, 'Comment_IsList')){
            return 500;
        }
        $ownerApp = $this->GetOwnerApp($owner->module);
        if (!$ownerApp->Comment_IsList($owner->type, $owner->ownerid)){
            return 403;
        }
        return 0;
    }

    public function IsCommentWrite(CommentOwner $owner){
        $owner = $this->OwnerNormalize($owner);
        if (!$this->OwnerAppFunctionExist($owner->module, 'Comment_IsWrite')){
            return 500;
        }
        $ownerApp = $this->GetOwnerApp($owner->module);
        if (!$ownerApp->Comment_IsWrite($owner->type, $owner->ownerid)){
            return 403;
        }
        return 0;
    }

    /**
     * @param $module
     * @param $type
     * @param $ownerid
     * @param $d
     * @return Comment
     */
    private function ReplyParser($owner, $d){
        $owner = $this->OwnerNormalize($owner);

        /** @var Comment $comment */
        $comment = $this->InstanceClass('Comment', $d);

        if ($comment->parentid > 0){
            $parentComment = $this->Comment($owner, $comment->parentid);
            if (is_integer($parentComment)){
                return null;
            }
        }

        $utm = Abricos::TextParser();
        $body = $utm->Parser($comment->body);
        if (empty($body)){
            return null;
        }
        $comment->body = $body;
        $comment->userid = Abricos::$user->id;
        $comment->dateline = TIMENOW;

        return $comment;
    }

    public function ReplyToJSON($owner, $d){
        $comment = $this->Reply($owner, $d);
        $ret = $this->ResultToJSON('reply', $comment);

        if (!is_integer($comment)){
            $ret = $this->ImplodeJSON(
                $this->CommentListToJSON($owner, $comment->id - 1),
                $ret
            );
        }
        return $ret;
    }

    public function Reply($owner, $d){
        if (($err = $this->IsCommentWrite($owner)) > 0){
            return $err;
        }

        $comment = $this->ReplyParser($owner, $d);
        if (empty($comment)){
            return 400;
        }

        $commentid = CommentQuery::CommentAppend($this, $owner, $comment);
        $comment->id = $commentid;

        $this->StatisticUpdate($owner);

        return $comment;
    }

    public function ReplyPreviewToJSON($owner, $d){
        $ret = $this->ReplyPreview($owner, $d);
        return $this->ResultToJSON('replyPreview', $ret);
    }

    public function ReplyPreview($owner, $d){
        if (($err = $this->IsCommentWrite($owner)) > 0){
            return $err;
        }

        $comment = $this->ReplyParser($owner, $d);
        if (empty($comment)){
            return 400;
        }

        return $comment;
    }

    public function CommentListToJSON($owner, $fromCommentId = 0){
        $ret = $this->CommentList($owner, $fromCommentId);
        return $this->ResultToJSON('commentList', $ret);
    }

    /**
     * @param $module
     * @param $type
     * @param $ownerid
     * @return CommentList|int
     */
    public function CommentList($owner, $fromCommentId = 0){
        $owner = $this->OwnerNormalize($owner);

        if (($err = $this->IsCommentView($owner)) > 0){
            return $err;
        }

        /** @var CommentList $list */
        $list = $this->InstanceClass('CommentList');
        $list->owner = $owner;

        $rows = CommentQuery::CommentList($this, $owner, $fromCommentId);
        $maxCommentId = 0;
        while (($d = $this->db->fetch_array($rows))){
            /** @var Comment $comment */
            $comment = $this->InstanceClass('Comment', $d);
            $maxCommentId = max($maxCommentId, $comment->id);
            $list->Add($comment);
        }

        if (Abricos::$user->id > 0){
            $userview = CommentQuery::UserView($this, $owner);
            if (!empty($userview)){
                $list->userview = intval($userview['commentid']);
            }
            CommentQuery::UserViewSave($this, $owner, $maxCommentId);
        }

        return $list;
    }

    /**
     * @param $module
     * @param $type
     * @param $ownerid
     * @param $commentid
     * @return Comment|int
     */
    public function Comment($owner, $commentid){
        $owner = $this->OwnerNormalize($owner);

        if (($err = $this->IsCommentView($owner)) > 0){
            return $err;
        }
        $d = CommentQuery::Comment($this, $owner, $commentid);
        if (empty($d)){
            return 404;
        }
        return $this->InstanceClass('Comment', $d);
    }

    /**
     * @param string $module Owner Module
     * @param string $type Owner Ids Type (Field Name)
     * @param int|array[int] $ownerid Owner Id
     */
    public function Statistic($module, $type, $ownerid){
        $rows = CommentQuery::StatisticList($this, $module, $type, [$ownerid]);
        $d = $this->db->fetch_array($rows);
        if (empty($d)){
            return null;
        }

        return $this->InstanceClass('Statistic', $d);
    }

    /**
     * @param string $module Owner Module
     * @param string $type Owner Ids Type (Field Name)
     * @param int|array[int] $ownerids Owner Ids
     * @return CommentStatisticList
     */
    public function StatisticList($module, $type, $ownerids){
        /** @var CommentStatisticList $list */
        $list = $this->InstanceClass('StatisticList');

        $rows = CommentQuery::StatisticList($this, $module, $type, $ownerids);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Statistic', $d));
        }
        return $list;
    }

    public function StatisticUpdate($module, $type, $ownerid){
        CommentQuery::StatisticUpdate($this, $module, $type, $ownerid);

        $statistic = $this->Statistic($module, $type, $ownerid);

        if ($this->OwnerAppFunctionExist($module, 'Comment_OnStatisticUpdate')){
            $ownerApp = $this->GetOwnerApp($module);
            $ownerApp->Comment_OnStatisticUpdate($type, $ownerid, $statistic);
        }
        return $statistic;
    }
}

?>