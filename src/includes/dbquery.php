<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class CommentQuery
 */
class CommentQuery {

    public static function CommentList(Ab_Database $db, $module, $type, $ownerid){
        $sql = "
            SELECT
                c.*
            FROM ".$db->prefix."comment_owner o
            INNER JOIN ".$db->prefix."comment c ON c.commentid=o.commentid
            WHERE o.ownerModule='".bkstr($module)."'
                AND o.ownerType='".bkstr($type)."'
                AND o.ownerid=".intval($ownerid)."
        ";
        return $db->query_read($sql);
    }

    public static function StatisticList(Ab_Database $db, $module, $type, $ownerids){
        $aw = array();
        $count = count($ownerids);
        if ($count === 0){
            return null;
        }

        for ($i = 0; $i < $count; $i++){
            $aw[] = "ownerid=".bkint($ownerids[$i]);
        }

        $sql = "
			SELECT DISTINCT
			  o.ownerid as id,
			  o.commentCount,
			  o.lastCommentid,
			  o.lastUserid,
			  o.lastCommentDate
			FROM ".$db->prefix."comment_ownerstat o
			WHERE ownerModule='".bkstr($module)."'
			    AND ownerType='".bkstr($type)."'
			    AND (".implode(" OR ", $aw).")
		";
        return $db->query_read($sql);
    }
}

?>