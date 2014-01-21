<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$contentId = Abricos::CleanGPC('g', 'contentid', TYPE_INT);
$manager = Abricos::GetModule('comment')->GetManager();

if (empty($contentId)){
	$contentId = Brick::$contentId;
}else{
	$brick->param->var['ondom'] = $brick->param->var['nonondom']; 
}

if ($contentId == 0){
	$brick->content = "";
	$brick->param->var = array();
	return;
}
$brick->param->var['cid'] = $contentId;

$data = array();
$rd = $manager->CommentsWithLastView($contentId);
// $rows = $manager->Comments($contentId); 
$lst = ""; $t = "";
$slst = "";
foreach ($rd->list as $row){
	if ($row['st'] == 1){
		$row['bd'] = '';
	}
	
	$lst .= Brick::ReplaceVarByData($brick->param->var['t'], array(
		"id" => $row['id'],
		"u" => $row['unm'],
		"c" => $row['bd']
	));
	unset($row['bd']);
	
	$slst .= str_replace('#c#', json_encode($row), $brick->param->var['si']);
}
$brick->param->var['lst'] = $lst;
$brick->param->var['slst'] = $slst;
$brick->param->var['lid'] = $rd->lastview;

if (Abricos::$user->id > 0){
	$brick->param->var['ft'] = $brick->param->var['ftreg']; 
}

if (!empty($brick->param->param['voting'])){
	$brick->param->var['voting'] = 'true';
}

?>