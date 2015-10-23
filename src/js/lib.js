var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['application.js']},
        {name: '{C#MODNAME}', files: ['model.js']}
    ]
};
Component.entryPoint = function(NS){

    var COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50
    });

    SYS.Application.build(COMPONENT, {}, {
        initializer: function(){
            var instance = this;
            this.appStructure(function(){
                NS.roles.load(function(){
                    instance.initCallbackFire();
                });
            }, this);
        }
    }, [], {
        APPS: {
            uprofile: {}
        },
        REQS: {
            commentList: {
                args: ['module', 'type', 'ownerid'],
                attribute: false,
                type: 'modelList:CommentList',
                onResponse: function(commentList){
                    var userIds = commentList.toArray('userid', {distinct: true});
                    if (userIds.length === 0){
                        return;
                    }
                    return function(callback, context){
                        this.getApp('uprofile').userListByIds(userIds, function(err, result){
                            var userList = result.userListByIds;
                            commentList.each(function(comment){
                                var user = userList.getById(comment.get('userid'));
                                comment.set('user', user);
                            }, this);
                            callback.call(context || null);
                        }, context);
                    };

                }
            },
            replyPreview: {
                args: ['module', 'type', 'ownerid', 'reply'],
                type: 'model:Comment'
            },
            reply: {
                args: ['module', 'type', 'ownerid', 'reply'],
                type: 'model:Comment'
            }
        },
        ATTRS: {
            Comment: {value: NS.Comment},
            CommentList: {value: NS.CommentList}
        }
    });

};