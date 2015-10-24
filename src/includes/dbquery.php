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

    public static function Comment(CommentApp $app, $module, $type, $ownerid, $commentid){
        $db = $app->db;
        $sql = "
            SELECT c.*
            FROM ".$db->prefix."comment_owner o
            INNER JOIN ".$db->prefix."comment c ON c.commentid=o.commentid
            WHERE o.ownerModule='".bkstr($module)."'
                AND o.ownerType='".bkstr($type)."'
                AND o.ownerid=".intval($ownerid)."
                AND o.commentid=".intval($commentid)."
            LIMIT 1
        ";
        return $db->query_first($sql);
    }

    public static function CommentAppend(CommentApp $app, $module, $type, $ownerid, Comment $comment){
        $db = $app->db;
        $sql = "
            INSERT INTO ".$db->prefix."comment
            (parentid, userid, body, dateline) VALUES (
                ".intval($comment->parentid).",
                ".intval(Abricos::$user->id).",
                '".bkstr($comment->body)."',
                ".TIMENOW."
            )
        ";
        $db->query_write($sql);
        $commentid = $db->insert_id();

        $sql = "
            INSERT INTO ".$db->prefix."comment_owner
            (ownerModule, ownerType, ownerid, commentid, userid, dateline) VALUES (
                '".bkstr($module)."',
                '".bkstr($type)."',
                ".intval($ownerid).",
                ".intval($commentid).",
                ".intval(Abricos::$user->id).",
                ".TIMENOW."
            )
        ";
        $db->query_write($sql);

        return $commentid;
    }

    public static function CommentList(CommentApp $app, $module, $type, $ownerid, $fromCommentId = 0){
        $db = $app->db;
        $sql = "
            SELECT c.*
            FROM ".$db->prefix."comment_owner o
            INNER JOIN ".$db->prefix."comment c ON c.commentid=o.commentid
            WHERE o.ownerModule='".bkstr($module)."'
                AND o.ownerType='".bkstr($type)."'
                AND o.ownerid=".intval($ownerid)."
                AND c.commentid>".bkint($fromCommentId)."
        ";
        return $db->query_read($sql);
    }

    public static function StatisticList(CommentApp $app, $module, $type, $ownerids){
        $db = $app->db;
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

    public static function StatisticUpdate(CommentApp $app, $module, $type, $ownerid){
        $db = $app->db;
        $sql = "
			 INSERT INTO ".$db->prefix."comment_ownerstat (
                ownerModule, ownerType, ownerid,
                commentCount,
                lastCommentid, lastUserid, lastCommentDate
            )(
                SELECT
                    o.ownerModule,
                    o.ownerType,
                    o.ownerid,
                    o1.cnt,
                    o.commentid,
                    o.userid,
                    o.dateline
                FROM ".$db->prefix."comment_owner o
                JOIN (
                    SELECT
                        ownerModule,
                        ownerType,
                        ownerid,
                        count(commentid) as cnt,
                        max(commentid) as lastid
                    FROM ".$db->prefix."comment_owner
                    WHERE ownerModule='".bkstr($module)."'
                        AND ownerType='".bkstr($type)."'
                        AND ownerid=".intval($ownerid)."
                    GROUP BY ownerModule, ownerType, ownerid
                ) as o1 ON o.ownerModule=o1.ownerModule
                        AND o.ownerType=o1.ownerType
                        AND o.ownerid=o1.ownerid
                        AND o.commentid=o1.lastid
            )
            ON DUPLICATE KEY UPDATE
                commentCount=o1.cnt,
                lastCommentid=o.commentid,
                lastUserid=o.userid,
                lastCommentDate=o.dateline
		";
        return $db->query_read($sql);
    }

    public static function UserView(CommentApp $app, $module, $type, $ownerid){
        $db = $app->db;
        $sql = "
			SELECT *
			FROM ".$db->prefix."comment_userview
			WHERE userid=".bkint(Abricos::$user->id)."
			    AND ownerModule='".bkstr($module)."'
			    AND ownerType='".bkstr($type)."'
			    AND ownerid=".intval($ownerid)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function UserViewSave(CommentApp $app, $module, $type, $ownerid, $commentid){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."comment_userview
			(ownerModule, ownerType, ownerid, userid, commentid, dateline) VALUES (
			    '".bkstr($module)."',
			    '".bkstr($type)."',
				".bkint($ownerid).",
				".bkint(Abricos::$user->id).",
				".bkint($commentid).",
				".TIMENOW."
			)
			ON DUPLICATE KEY UPDATE
				commentid=".bkint($commentid).",
				dateline=".TIMENOW."
		";
        $db->query_write($sql);
    }
}

?>