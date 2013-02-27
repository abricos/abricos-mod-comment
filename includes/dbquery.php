<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class CommentQuery {
	
	const STATUS_OK = 0;
	const STATUSS_SPAM = 1;
	
	public static function SpamSet(Ab_Database $db, $commentId, $newStatus){
		$sql = "
			UPDATE ".$db->prefix."cmt_comment
			SET status='".bkstr($newStatus)."'
			WHERE commentid='".bkint($commentId)."'
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function FullListCount(Ab_Database $db){
		$sql = "
			SELECT count(commentid) as cnt 
			FROM ".$db->prefix."cmt_comment a
			INNER JOIN ".$db->prefix."content c ON a.contentid = c.contentid
			WHERE c.modman='blog'
		";
		return $db->query_read($sql);
	}
	
	public static function FullList(Ab_Database $db, $page, $limit){
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
			INNER JOIN ".$db->prefix."user u ON u.userid = a.userid
			INNER JOIN ".$db->prefix."content c ON a.contentid = c.contentid
			WHERE c.modman='blog'
			ORDER BY a.dateline DESC
			LIMIT ".$from.",".bkint($limit)."
		";
		return $db->query_read($sql);
	}
	
	public static function Append(Ab_Database $db, $contentid, $d){
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
	
	private static function CommentRatingSQLExt(Ab_Database $db){
		$ret = new stdClass();
		$ret->fld = "";
		$ret->tbl = "";
		$userid = Abricos::$user->id;
		
		if (BlogManager::$isURating && $userid>0){
			$ret->fld .= "
				,
				IF(ISNULL(vc.voteval), 0, vc.voteval) as rtg,
				IF(ISNULL(vc.votecount), 0, vc.votecount) as vcnt,
				IF(ISNULL(vt.userid), null, IF(vt.voteup>0, 1, IF(vt.votedown>0, -1, 0))) as vmy
			";
			$ret->tbl .= "
				LEFT JOIN ".$db->prefix."urating_vote vt
					ON vt.module='comment'
					AND vt.elementid=a.commentid
					AND vt.userid=".bkint($userid)."
				LEFT JOIN ".$db->prefix."urating_votecalc vc
					ON vc.module='comment'
					AND vc.elementid=a.commentid
			";
		}
		return $ret;
	}
	
	
	public static function Comments(Ab_Database $db, $contentid, $lastid = 0){
		$urt = CommentQuery::CommentRatingSQLExt($db);
		
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
				".$urt->fld."
			FROM ".$db->prefix."cmt_comment a
			LEFT JOIN ".$db->prefix."user u ON u.userid = a.userid
			".$urt->tbl."
			WHERE a.contentid =".bkint($contentid)." AND a.commentid > ".bkint($lastid)."
			ORDER BY a.commentid 
		";
		return $db->query_read($sql);
	}
	
	public static function Comment(Ab_Database $db, $commentid, $contentid, $retarray = false){
		$urt = CommentQuery::CommentRatingSQLExt($db);
		
		$sql = "
			SELECT 
				a.commentid as id, 
				a.parentcommentid as pid, 
				a.dateedit as de,
				IF(a.status=".CommentQuery::STATUSS_SPAM.", '', a.body) as bd, 
				a.status as st, 
				u.userid as uid,
				u.avatar as avt,
				u.firstname as fnm,
				u.lastname as lnm
				".$urt->fld."
			FROM ".$db->prefix."cmt_comment a
			LEFT JOIN ".$db->prefix."user u ON u.userid = a.userid
			".$urt->tbl."
			WHERE a.contentid =".bkint($contentid)." AND a.commentid = ".bkint($commentid)."
			LIMIT 1
		";
		return $retarray ? $db->query_first($sql) : $db->query_read($sql);
	}
	
	public static function CommentInfo(Ab_Database $db, $commentid){
		$sql = "
			SELECT 
				cmt.commentid as id,
				cmt.userid as uid,
				cmt.contentid as ctid,
				ct.modman as m,
				ct.body as bd
			FROM ".$db->prefix."cmt_comment cmt
			LEFT JOIN ".$db->prefix."content ct ON cmt.contentid = ct.contentid
			WHERE cmt.commentid = ".bkint($commentid)."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function LastView(Ab_Database $db, $userid, $contentid){
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
	
	public static function LastViewAppend(Ab_Database $db, $userid, $contentid, $commentid){
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
	
	public static function LastViewUpdate(Ab_Database $db, $userid, $contentid, $commentid){
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