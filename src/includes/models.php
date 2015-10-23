<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class CommentStatistic
 *
 * @property CommentFileList $files
 */
class CommentStatistic extends AbricosModel {
    protected $_structModule = 'comment';
    protected $_structName = 'Statistic';
}

/**
 * Class CommentStatisticList
 * @method CommentStatistic Get($topicid)
 * @method CommentStatistic GetByIndex($index)
 */
class CommentStatisticList extends AbricosModelList {
}

/**
 * Class Comment
 *
 * @property int $parentid
 * @property int $userid
 * @property string $body
 * @property int $dateline
 */
class Comment extends AbricosModel {
    protected $_structModule = 'comment';
    protected $_structName = 'Comment';
}

/**
 * Class CommentList
 * @method Comment Get($commentid)
 * @method Comment GetByIndex($index)
 */
class CommentList extends AbricosModelList {

    /**
     * Last viewed by the current user comment
     * @var int
     */
    public $userview = 0;

    public function ToJSON(){
        $ret = parent::ToJSON();
        $ret->userview = $this->userview;
        return $ret;
    }
}

?>