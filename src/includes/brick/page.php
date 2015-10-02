<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$v = &$brick->param->var;
$contentId = Abricos::CleanGPC('g', 'contentid', TYPE_INT);
$manager = Abricos::GetModule('comment')->GetManager();

if (empty($contentId)){
    $contentId = Brick::$contentId;
} else {
    $v['ondom'] = $v['nonondom'];
}

if ($contentId == 0){
    $brick->content = "";
    $v = array();
    return;
}
$v['cid'] = $contentId;

$data = array();
$rd = $manager->CommentsWithLastView($contentId);
$lst = "";
$t = "";
$slst = "";
foreach ($rd->list as $row){
    if ($row['st'] == 1){
        $row['bd'] = '';
    }

    $lst .= Brick::ReplaceVarByData($v['t'], array(
        "id" => $row['id'],
        "u" => $row['unm'],
        "c" => $row['bd']
    ));
    unset($row['bd']);

    $slst .= str_replace('#c#', json_encode($row), $v['si']);
}
$v['lst'] = $lst;
$v['slst'] = $slst;
$v['lid'] = $rd->lastview;

if (!empty($brick->param->param['voting'])){
    $v['voting'] = 'true';
}

?>