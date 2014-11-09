/*
 @version $Id$
 @copyright Copyright (C) 2008 Abricos All rights reserved.
 @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [{name: 'user', files: ['cpanel.js']}]
};
Component.entryPoint = function(){

    if (!Brick.AppRoles.check('comment', '50')){
        return;
    }

    var cp = Brick.mod.user.cp;

    var menuItem = new cp.MenuItem(this.moduleName);
    menuItem.icon = '/modules/comment/images/cp_icon.gif';
    menuItem.entryComponent = 'api';
    menuItem.entryPoint = 'Brick.mod.comment.API.showCommentListWidget';

    cp.MenuManager.add(menuItem);
};
