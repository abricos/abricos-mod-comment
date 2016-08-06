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
class old_CommentQuery {

    const STATUS_OK = 0;
    const STATUSS_SPAM = 1;

    public static function SpamSet(Ab_Database $db, $commentId, $newStatus) {
        $sql = "
			UPDATE ".$db->prefix."cmt_comment
			SET status='".bkstr($newStatus)."'
			WHERE commentid='".bkint($commentId)."'
			LIMIT 1
		";
        $db->query_write($sql);
    }


    private static function CommentRatingSQLExt(Ab_Database $db) {
        $ret = new stdClass();
        $ret->fld = "";
        $ret->tbl = "";
        $userid = Abricos::$user->id;
        $votePeriod = TIMENOW - 60 * 60 * 24 * 31;

        if (CommentManager::$isURating && $userid > 0) {
            $ret->fld .= "
				,
				IF(ISNULL(vc.voteval), 0, vc.voteval) as rtg,
				IF(ISNULL(vc.votecount), 0, vc.votecount) as vcnt,
				IF(a.dateline<".$votePeriod.", 0, IF(ISNULL(vt.userid), null, IF(vt.voteup>0, 1, IF(vt.votedown>0, -1, 0)))) as vmy
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

}
