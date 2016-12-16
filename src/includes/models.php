<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class CommentOwner
 *
 * @property string $module
 * @property string $type
 * @property int $ownerid
 */
class CommentOwner extends AbricosModel {
    protected $_structModule = 'comment';
    protected $_structName = 'Owner';
}

/**
 * Class CommentStatistic
 *
 * @property int $count
 * @property int $lastid
 * @property int $lastUserid
 * @property int $lastDate
 *
 * @property UProfileUser $lastUser
 */
class CommentStatistic extends AbricosModel {
    protected $_structModule = 'comment';
    protected $_structName = 'Statistic';

    public function __get($name){
        switch ($name){
            case 'lastUser':
                /** @var UProfileApp $uprofileApp */
                $uprofileApp = Abricos::GetApp('uprofile');
                return $uprofileApp->User($this->lastUserid);
        }
        return parent::__get($name);
    }

}

/**
 * Class CommentStatisticList
 *
 * @method CommentStatistic Get($id)
 * @method CommentStatistic GetByIndex($index)
 */
class CommentStatisticList extends AbricosModelList {

    /**
     * @param CommentStatistic $item
     */
    public function Add($item){
        /** @var UProfileApp $uprofileApp */
        $uprofileApp = Abricos::GetApp('uprofile');
        $uprofileApp->UserAddToPreload($item->lastUserid);

        return parent::Add($item);
    }

}

/**
 * Class Comment
 *
 * @property CommentApp $app
 *
 * @property int $parentid
 * @property int $userid
 * @property string $body
 * @property int $dateline
 * @property URatingVoting $voting
 *
 * @property UProfileUser $user
 */
class Comment extends AbricosModel {
    protected $_structModule = 'comment';
    protected $_structName = 'Comment';

    public function __get($name){
        switch ($name){
            case 'user':
                /** @var UProfileApp $uprofileApp */
                $uprofileApp = Abricos::GetApp('uprofile');
                return $uprofileApp->User($this->userid);
        }
        return parent::__get($name);
    }
}

/**
 * Class CommentList
 * @method Comment Get($commentid)
 * @method Comment GetByIndex($index)
 */
class CommentList extends AbricosModelList {

    /**
     * Last viewed by the current user comment
     *
     * @var int
     */
    public $userview = 0;

    /**
     * @var CommentOwner
     */
    public $owner;

    /**
     * @param Comment $item
     */
    public function Add($item){
        /** @var UProfileApp $uprofileApp */
        $uprofileApp = Abricos::GetApp('uprofile');
        $uprofileApp->UserAddToPreload($item->userid);

        return parent::Add($item);
    }

    public function ToJSON(){
        $ret = parent::ToJSON();
        $ret->userview = $this->userview;
        $ret->owner = $this->owner->ToJSON();
        return $ret;
    }
}
