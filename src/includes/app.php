<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

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
                return $this->CommentListToJSON($d->options);
        }
        return null;
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

    public function IsCommentView($owner){
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

    /**
     * @param $owner
     * @return int Error code: 0-not error
     */
    public function IsCommentWrite($owner){
        $owner = $this->OwnerNormalize($owner);
        if (!$this->OwnerAppFunctionExist($owner->module, 'Comment_IsWrite')){
            return AbricosResponse::ERR_SERVER_ERROR;
        }
        $ownerApp = $this->GetOwnerApp($owner->module);
        if (!$ownerApp->Comment_IsWrite($owner->type, $owner->ownerid)){
            return AbricosResponse::ERR_FORBIDDEN;
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

        if (AbricosResponse::IsError($comment)){
            return $ret;
        }
        return $this->ImplodeJSON(
            $this->CommentListToJSON($owner, $owner->userview),
            $ret
        );
    }

    public function Reply($owner, $d){
        $owner = $this->OwnerNormalize($owner);

        if (($err = $this->IsCommentWrite($owner)) > 0){
            return $err;
        }

        $comment = $this->ReplyParser($owner, $d);
        if (empty($comment)){
            return AbricosResponse::ERR_BAD_REQUEST;
        }

        $commentid = CommentQuery::CommentAppend($this, $owner, $comment);
        $comment->id = $commentid;

        $this->StatisticUpdate($owner);

        if ($this->OwnerAppFunctionExist($owner->module, 'Comment_SendNotify')){
            $parentComment = $this->Comment($owner, $comment->parentid);
            if (AbricosResponse::IsError($parentComment)){
                $parentComment = null;
            }
            $ownerApp = $this->GetOwnerApp($owner->module);
            $ownerApp->Comment_SendNotify($owner->type, $owner->ownerid, $comment, $parentComment);
        }

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
            return AbricosResponse::ERR_BAD_REQUEST;
        }

        return $comment;
    }


    public function CommentListToJSON($options, $fromCommentId = 0){
        $ret = $this->CommentList($options, $fromCommentId);
        return $this->ResultToJSON('commentList', $ret);
    }

    /**
     * @param $options
     * @param int $fromCommentId
     * @return CommentList|int
     */
    public function CommentList($options, $fromCommentId = 0){
        $owner = $this->OwnerNormalize($options);

        if (($err = $this->IsCommentView($owner)) > 0){
            return $err;
        }

        /** @var CommentList $list */
        $list = $this->InstanceClass('CommentList');
        $list->owner = $owner;
        $notBody = isset($options->notBody) ? !!$options->notBody : false;

        $rows = CommentQuery::CommentList($this, $owner, $fromCommentId, $notBody);
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

        /** @var URatingApp $uratingApp */
        $uratingApp = Abricos::GetApp('urating');
        $votingList = null;
        if (!empty($uratingApp) && $list->Count() > 0){
            $commentIds = $list->ToArray('id');
            $votingList = $uratingApp->VotingList($owner->module, $owner->type.'-comment', $commentIds);

            $count = $list->Count();
            for ($i = 0; $i < $count; $i++){
                $comment = $list->GetByIndex($i);
                $comment->voting = $votingList->GetByOwnerId($comment->id);
                $comment->voting->ownerDate = $comment->dateline;
                $comment->voting->userid = $comment->userid;
            }
        }

        return $list;
    }

    /**
     * @param $owner
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
            return AbricosResponse::ERR_NOT_FOUND;
        }
        $comment = $this->InstanceClass('Comment', $d);
        return $comment;
    }

    public function OwnerIdByCommentId($module, $type, $commentid){
        $d = CommentQuery::OwnerIdByCommentId($this, $module, $type, $commentid);
        if (empty($d)){
            return 0;
        }
        return intval($d['ownerid']);
    }

    /**
     * @param string $module Owner Module
     * @param string $type Owner Ids Type (Field Name)
     * @param int|array[int] $ownerid Owner Id
     */
    public function Statistic(CommentOwner $owner){
        $rows = CommentQuery::StatisticList($this, $owner->module, $owner->type, array($owner->ownerid));
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

    public function StatisticUpdate($owner){
        $owner = $this->OwnerNormalize($owner);

        CommentQuery::StatisticUpdate($this, $owner);

        $statistic = $this->Statistic($owner);

        if ($this->OwnerAppFunctionExist($owner->module, 'Comment_OnStatisticUpdate')){
            $ownerApp = $this->GetOwnerApp($owner->module);
            $ownerApp->Comment_OnStatisticUpdate($owner->type, $owner->ownerid, $statistic);
        }
        return $statistic;
    }
}
