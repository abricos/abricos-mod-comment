<?php
/**
 * @package Abricos
 * @subpackage Comment
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


function CommentApp_BuildCommentList(CommentList $list, Ab_CoreBrick $brick, $parentid){
    $count = $list->Count();
    $lst = "";
    for ($i = 0; $i < $count; $i++){
        $comment = $list->GetByIndex($i);
        if ($comment->parentid !== $parentid){
            continue;
        }

        $replace = array(
            "userURI" => "",
            "userName" => "",
            "userAvatar" => "",
            "date" => rusDateTime($comment->dateline),
            "body" => $comment->body
        );

        $user = $comment->user;
        if (!empty($user)){
            $replace["userURI"] = $user->URI();
            $replace["userName"] = $user->GetViewName();
            $replace["userAvatar"] = $user->GetAvatar45();
        }

        $replace['list'] = CommentApp_BuildCommentList($list, $brick, $comment->id);
        $lst .= Brick::ReplaceVarByData($brick->param->var['item'], $replace);
    }

    return $lst;
}

?>