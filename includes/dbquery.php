<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Forum
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

class CommentQuery {
	
	const STATUS_OK = 0;
	const STATUSS_SPAM = 1;
	
	public static function SpamSet(CMSDatabase $db, $commentId, $newStatus){
		$sql = "
			UPDATE ".$db->prefix."cmt_comment
			SET status='".bkstr($newStatus)."'
			WHERE commentid='".bkint($commentId)."'
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function FullListCount(CMSDatabase $db){
		$sql = "
			SELECT count(commentid) as cnt 
			FROM ".$db->prefix."cmt_comment
		";
		return $db->query_read($sql);
	}
	
	public static function FullList(CMSDatabase $db, $page, $limit){
		$from = (($page-1)*$limit);
		$sql = "
			SELECT 
				a.commentid as id, 
				a.parentcommentid as pid, 
				a.dateline as dl, 
				a.dateedit as de, 
				a.body as bd, 
				a.status as st, 
				u.userid as uid, 
				u.username as unm,
				0 as ugp
			FROM ".$db->prefix."cmt_comment a
			LEFT JOIN ".$db->prefix."user u ON u.userid = a.userid
			ORDER BY a.dateline DESC
			LIMIT ".$from.",".bkint($limit)."
		";
		return $db->query_read($sql);
	}
	
	public static function Append(CMSDatabase $db, $contentid, $d){
		$sql = "
			INSERT INTO ".$db->prefix."cmt_comment (
				contentid, 
				parentcommentid, 
				userid, 
				dateline,
				dateedit, 
				body
			)
			VALUES (
			".bkint($contentid).",
			".bkint($d->pid).",
			".bkint($d->uid).",
			".TIMENOW.",
			".TIMENOW.",
			'".bkstr($d->bd)."'
		)";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function Comments(CMSDatabase $db, $contentid, $lastid = 0){
		$sql = "
			SELECT 
				a.commentid as id, 
				a.parentcommentid as pid, 
				a.dateedit as de,
				IF(a.status=".CommentQuery::STATUSS_SPAM.", '', a.body) as bd, 
				a.status as st, 
				u.userid as uid, 
				u.username as unm,
				u.avatar as avt,
				u.firstname as fnm,
				u.lastname as lnm
			FROM ".$db->prefix."cmt_comment a
			LEFT JOIN ".$db->prefix."user u ON u.userid = a.userid
			WHERE a.contentid =".bkint($contentid)." AND a.commentid > ".bkint($lastid)."
			ORDER BY a.commentid 
		";
		return $db->query_read($sql);
	}
	
	public static function Comment(CMSDatabase $db, $commentid, $contentid, $retarray = false){
		$sql = "
			SELECT 
				a.commentid as id, 
				a.parentcommentid as pid, 
				a.dateedit as de,
				IF(a.status=".CommentQuery::STATUSS_SPAM.", '', a.body) as bd, 
				a.status as st, 
				u.avatar as avt,
				u.firstname as fnm,
				u.lastname as lnm
			FROM ".$db->prefix."cmt_comment a
			LEFT JOIN ".$db->prefix."user u ON u.userid = a.userid
			WHERE a.contentid =".bkint($contentid)." AND a.commentid = ".bkint($commentid)."
			LIMIT 1
		";
		return $retarray ? $db->query_first($sql) : $db->query_read($sql);
	}
	
	public static function LastView(CMSDatabase $db, $userid, $contentid){
		$sql = "
			SELECT 
				commentid as id,
				dateline as dl
			FROM ".$db->prefix."cmt_lastview
			WHERE userid=".bkint($userid)." AND contentid=".bkint($contentid)."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function LastViewAppend(CMSDatabase $db, $userid, $contentid, $commentid){
		$sql = "
			INSERT INTO ".$db->prefix."cmt_lastview (contentid, userid, commentid, dateline) VALUES (
				".bkint($contentid).",
				".bkint($userid).",
				".bkint($commentid).",
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function LastViewUpdate(CMSDatabase $db, $userid, $contentid, $commentid){
		$sql = "
			UPDATE ".$db->prefix."cmt_lastview
			SET commentid=".bkint($commentid)."
			WHERE userid=".bkint($userid)." AND contentid=".bkint($contentid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
}

?>