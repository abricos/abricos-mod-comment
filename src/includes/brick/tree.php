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
$app = Abricos::GetApp('comment');

$owner = $app->OwnerNormalize(array(
    "module" => $p['module'],
    "type" => $p['type'],
    "ownerid" => $p['ownerid']
));

$commentList = $app->CommentList($owner);
if (AbricosResponse::IsError($commentList)){
    $brick->content = "";
    return;
}

$module->ScriptRequireOnce('includes/brick/functions.php');

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "count" => $commentList->Count(),
    "brickid" => $brick->id,
    "module" => $owner->module,
    "type" => $owner->type,
    "ownerid" => $owner->ownerid,
    "tree" => CommentApp_BuildCommentTree($commentList, $brick, 0)
));
