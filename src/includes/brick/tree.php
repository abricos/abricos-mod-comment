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
$p = &$brick->param->param;

/** @var CommentModule $module */
$module = Abricos::GetModule('comment');

/** @var CommentApp $app */
$app = $module->GetManager()->GetApp();

$module->ScriptRequireOnce('includes/brick/functions.php');

$owner = $app->OwnerNormalize(array(
    "module" => $p['module'],
    "type" => $p['type'],
    "ownerid" => $p['ownerid']
));

$commentList = $app->CommentList($owner);
$commentList->FillUsers();

if (is_integer($commentList)){
    $brick->content = "";
    return;
}

$writeScript = "";
if ($app->IsCommentWrite($owner) === 0){
    $writeScript = Brick::ReplaceVarByData($v['writeScript'], array(
        "brickid" => $brick->id,
        "ownerModule" => $p['module'],
        "ownerType" => $p['type'],
        "ownerid" => $p['ownerid']
    ));
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "count" => $commentList->Count(),
    "writeScript" => $writeScript,
    "brickid" => $brick->id,
    "tree" => CommentApp_BuildCommentTree($commentList, $brick, 0)
));

?>