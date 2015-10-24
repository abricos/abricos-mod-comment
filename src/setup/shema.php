<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = Ab_UpdateManager::$current;
$db = Abricos::$db;
$pfx = $db->prefix;

if ($updateManager->isUpdate('0.3.1')){
    Abricos::GetModule('comment')->permission->Install();
}

if ($updateManager->isUpdate('0.4.3')){
    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."comment (
            commentid int(10) UNSIGNED NOT NULL auto_increment,
			parentid int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
            userid int(10) UNSIGNED NOT NULL COMMENT 'User ID',

			body text NOT NULL COMMENT '',

			status tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-OK, 1-SPAM',

			dateline int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Create Date',
			upddate int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Update Date',

            PRIMARY KEY (commentid),
            KEY userid (userid)
        )".$charset
    );

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."comment_owner (
            ownerModule VARCHAR(50) NOT NULL COMMENT 'Owner Module Name',
            ownerType VARCHAR(16) NOT NULL DEFAULT '' COMMENT 'Owner Type',
            ownerid int(10) UNSIGNED NOT NULL COMMENT 'Owner ID',

            commentid int(10) UNSIGNED NOT NULL COMMENT 'Comment ID',
            userid int(10) UNSIGNED NOT NULL COMMENT 'User ID',

            dateline int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Create Date',

            UNIQUE KEY comment (ownerModule, ownerType, ownerid, commentid),
            KEY userid (userid)
        )".$charset
    );

    // Owner Statistic
    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."comment_ownerstat (
            ownerModule VARCHAR(50) NOT NULL COMMENT 'Owner Module Name',
            ownerType VARCHAR(16) NOT NULL DEFAULT '' COMMENT 'Owner Type',
            ownerid int(10) UNSIGNED NOT NULL COMMENT 'Owner ID',

            commentCount int(5) UNSIGNED NOT NULL COMMENT '',

            lastCommentid int(10) UNSIGNED NOT NULL COMMENT 'Last Comment ID',
            lastUserid int(10) UNSIGNED NOT NULL COMMENT 'User ID in Last Comment',
			lastCommentDate int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Date in Last Comment',

            UNIQUE KEY ownerstat (ownerModule, ownerType, ownerid),
            KEY lastCommentDate (lastCommentDate)
        )".$charset
    );

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."comment_userview (
            ownerModule VARCHAR(50) NOT NULL COMMENT 'Owner Module Name',
            ownerType VARCHAR(16) NOT NULL DEFAULT '' COMMENT 'Owner Type',
            ownerid int(10) UNSIGNED NOT NULL COMMENT 'Owner ID',
            userid int(10) UNSIGNED NOT NULL COMMENT 'User ID in Last Comment',

            commentid int(10) UNSIGNED NOT NULL COMMENT 'Last Comment ID',

            dateline int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'View Date',
            UNIQUE KEY userview (ownerModule, ownerType, ownerid, userid)
        )".$charset
    );
}

if ($updateManager->isUpdate('0.4.3') && !$updateManager->isInstall()){

    /* * * * * * * * Prev version Table * * * * * * * * *
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."cmt_lastview (
		  lastviewid int(10) UNSIGNED NOT NULL auto_increment,
		  contentid int(10) UNSIGNED NOT NULL,
		  userid int(10) UNSIGNED NOT NULL,
		  commentid int(10) UNSIGNED NOT NULL,
		  dateline int(10) UNSIGNED NOT NULL,
		  PRIMARY KEY  (lastviewid),
		  KEY userid (userid),
		  KEY contentid (contentid)
		)".$charset
    );
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."cmt_comment (
			commentid int(10) UNSIGNED NOT NULL auto_increment,
			parentcommentid int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
			contentid int(10) UNSIGNED NOT NULL DEFAULT 0,
			userid int(10) UNSIGNED NOT NULL DEFAULT 0,
			body text NOT NULL,
			status int(2) UNSIGNED NOT NULL DEFAULT 0,
			
			dateline int(10) UNSIGNED NOT NULL DEFAULT 0,
			dateedit int(10) UNSIGNED NOT NULL DEFAULT 0,
			deldate int(10) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY  (commentid),
			KEY dateedit (dateedit),
			KEY contentid (contentid)
		)".$charset
    );
    /** * * * * * * * * * * * * * * * * * * * * * * * * **/

    $db->query_write("
        INSERT INTO ".$pfx."comment
        (commentid, parentid, userid, body, status, dateline, upddate)
        SELECT
            c.commentid,
            c.parentcommentid,
            c.userid,
            c.body,
            c.status,
            c.dateline,
            c.dateedit
        FROM ".$pfx."cmt_comment c
    ");

    $db->query_write("
        INSERT INTO ".$pfx."comment_owner
        (ownerModule, ownerType, ownerid, commentid, userid, dateline)
        SELECT
            t.modman,
            'content',
            c.contentid,
            c.commentid,
            c.userid,
            c.dateline
        FROM ".$pfx."cmt_comment c
        INNER JOIN ".$pfx."content t ON t.contentid=c.contentid
    ");

    $db->query_write("
        INSERT INTO ".$pfx."comment_ownerstat (
            ownerModule, ownerType, ownerid,
            commentCount,
            lastCommentid, lastUserid, lastCommentDate
        )
        SELECT
            o.ownerModule,
            o.ownerType,
            o.ownerid,
            o1.cnt,
            o.commentid,
            o.userid,
            o.dateline
        FROM ".$pfx."comment_owner o
        JOIN (
            SELECT
                ownerModule,
                ownerType,
                ownerid,
                count(commentid) as cnt,
                max(commentid) as lastid
            FROM ".$pfx."comment_owner
            GROUP BY ownerModule, ownerType, ownerid
        ) as o1 ON o.ownerModule=o1.ownerModule
                AND o.ownerType=o1.ownerType
                AND o.ownerid=o1.ownerid
                AND o.commentid=o1.lastid
    ");

    $db->query_write("DROP TABLE IF EXISTS ".$pfx."cmt_comment");
    $db->query_write("DROP TABLE IF EXISTS ".$pfx."cmt_lastview");
}

?>