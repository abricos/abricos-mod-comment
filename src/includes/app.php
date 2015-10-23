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
            'Statistic' => 'CommentStatistic',
            'StatisticList' => 'CommentStatisticList',
            'Comment' => 'Comment',
            'CommentList' => 'CommentList'
        );
    }

    protected function GetStructures(){
        return 'Statistic,Comment';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'replyPreview':
                return $this->ReplyPreviewToJSON($d->module, $d->type, $d->ownerid, $d->reply);
            case 'commentList':
                return $this->CommentListToJSON($d->module, $d->type, $d->ownerid);
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

    public function IsCommentView($module, $type, $ownerid){
        if (!$this->OwnerAppFunctionExist($module, 'Comment_IsList')){
            return 500;
        }
        $ownerApp = $this->GetOwnerApp($module);
        if (!$ownerApp->Comment_IsList($type, $ownerid)){
            return 403;
        }
        return 0;
    }

    public function IsCommentWrite($module, $type, $ownerid){
        if (!$this->OwnerAppFunctionExist($module, 'Comment_IsWrite')){
            return 500;
        }
        $ownerApp = $this->GetOwnerApp($module);
        if (!$ownerApp->Comment_IsWrite($type, $ownerid)){
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
    private function ReplyParser($module, $type, $ownerid, $d){
        /** @var Comment $comment */
        $comment = $this->InstanceClass('Comment', $d);

        if ($comment->parentid > 0){
            $parentComment = $this->Comment($module, $type, $ownerid, $comment->parentid);
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

        return $comment;
    }

    public function ReplyPreviewToJSON($module, $type, $ownerid, $d){
        $ret = $this->ReplyPreview($module, $type, $ownerid, $d);
        return $this->ResultToJSON('replyPreview', $ret);
    }

    public function ReplyPreview($module, $type, $ownerid, $d){
        if (($err = $this->IsCommentWrite($module, $type, $ownerid)) > 0){
            return $err;
        }

        $comment = $this->ReplyParser($module, $type, $ownerid, $d);
        if (empty($comment)){
            return 400;
        }

        return $comment;
    }

    public function CommentListToJSON($module, $type, $ownerid){
        $ret = $this->CommentList($module, $type, $ownerid);
        return $this->ResultToJSON('commentList', $ret);
    }

    /**
     * @param $module
     * @param $type
     * @param $ownerid
     * @return CommentList|int
     */
    public function CommentList($module, $type, $ownerid){
        if (($err = $this->IsCommentView($module, $type, $ownerid)) > 0){
            return $err;
        }

        /** @var CommentList $list */
        $list = $this->InstanceClass('CommentList');

        $rows = CommentQuery::CommentList($this->db, $module, $type, $ownerid);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Comment', $d));
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
    public function Comment($module, $type, $ownerid, $commentid){
        if (($err = $this->IsCommentView($module, $type, $ownerid)) > 0){
            return $err;
        }
        $d = CommentQuery::Comment($this->db, $module, $type, $ownerid, $commentid);
        if (empty($d)){
            return 404;
        }
        return $this->InstanceClass('Comment', $d);
    }

    /**
     * @param string $module Owner Module
     * @param string $type Owner Ids Type (Field Name)
     * @param int|array[int] $ownerid Owner Id
     *
     */
    public function Statistic($module, $type, $ownerid){
        $rows = CommentQuery::StatisticList($this->db, $module, $type, [$ownerid]);
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

        $rows = CommentQuery::StatisticList($this->db, $module, $type, $ownerids);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Statistic', $d));
        }
        return $list;
    }

}

?>