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

if ($updateManager->isInstall()){
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."cmt_comment (
			commentid int(10) UNSIGNED NOT NULL auto_increment,
			parentcommentid int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Комментарий родитель',
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

}

if ($updateManager->isUpdate('0.3.1')){
    Abricos::GetModule('comment')->permission->Install();
}

if ($updateManager->isUpdate('0.3.2')){
    // таблица будет хранить идентификатор последнего прочитавшего комментария
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
}


?>