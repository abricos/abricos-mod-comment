<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Comment
 * @copyright Copyright (C) 2008 Abricos All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

// оставлять комментарии могут только зарегистрированные пользователи
if (Abricos::$user->id == 0){ return; }

$brick = Brick::$builder->brick;
$user = &Abricos::$user->info;

$p_do = Abricos::CleanGPC('g', 'do', TYPE_STR);
$p_contentId = Abricos::CleanGPC('g', 'contentid', TYPE_INT);
$p_comment = Abricos::CleanGPC('p', 'comment', TYPE_STR);
$p_last = Abricos::CleanGPC('g', 'last', TYPE_INT);

$brick->param->var['cid'] = $p_contentId;

if ($p_do == "send" && !empty($p_comment)){

	$p_commentId = Abricos::CleanGPC('g', 'commentid', TYPE_INT);
	
	$allowTags = array(
    'b', 'strong', 'i', 'em', 'u','a',
    'p', 'sup', 'sub', 'div', 'img', 'span',
    'font', 'br', 'ul', 'ol', 'li'
	);
	$p_comment = strip_tags($p_comment, '<'.implode('><', $allowTags).'>');
	
	$data = array();
	$data['userid'] = $user['userid'];
	$data['contentid'] = $p_contentId;
	$data['parentcommentid'] = $p_commentId;
	$data['body'] = $p_comment;

	$newCommentId = CommentQuery::Append(Abricos::$db, $data);
	$data['commentid'] = $newCommentId; 
	
	/* Отправка писем уведомлений */
	
	$contentinfo = Ab_CoreQuery::ContentInfo(Abricos::$db, $p_contentId);

	if (!empty($contentinfo)){
		$module = Abricos::GetModule('comment');
		$module->commentData = $data;
		
		$module = Abricos::GetModule($contentinfo['modman']);
		$module->OnComment();
	}
}

$rows = CommentQuery::Comments(Abricos::$db, $p_contentId, $p_last);

while (($row = Abricos::$db->fetch_array($rows))){
	if ($row['st'] == 1){
		$row['bd'] = '';
	}
	$brick->param->var['lst'] .= str_replace("#c#", 
		json_encode($row), $brick->param->var['i']
	);
}
?>