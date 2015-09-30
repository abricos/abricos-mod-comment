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
