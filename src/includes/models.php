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
 */
class CommentStatistic extends AbricosModel {
    protected $_structModule = 'comment';
    protected $_structName = 'Statistic';

    /**
     * @var UProfileUser
     */
    public $lastUser;
}

/**
 * Class CommentStatisticList
 * @method CommentStatistic Get($id)
 * @method CommentStatistic GetByIndex($index)
 */
class CommentStatisticList extends AbricosModelList {
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
 */
class Comment extends AbricosModel {
    protected $_structModule = 'comment';
    protected $_structName = 'Comment';

    /**
     * @var UProfileUser
     */
    public $user;

    /**
     * @param UProfileUserList $userList
     */
    public function FillUsers($userList = null){
        if (empty($userList)){
            $userList = $this->app->UProfileApp()->UserListByIds($this->userid);
        }
        $this->user = $userList->Get($this->userid);
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

    public function FillUsers(){
        $count = $this->Count();
        $userids = array();
        for ($i = 0; $i < $count; $i++){
            $comment = $this->GetByIndex($i);
            $userids[] = $comment->userid;
        }

        $userList = $this->app->UProfileApp()->UserListByIds($userids);

        for ($i = 0; $i < $count; $i++){
            $comment = $this->GetByIndex($i);
            $comment->FillUsers($userList);
        }
    }

    public function ToJSON(){
        $ret = parent::ToJSON();
        $ret->userview = $this->userview;
        $ret->owner = $this->owner->ToJSON();
        return $ret;
    }
}

?>