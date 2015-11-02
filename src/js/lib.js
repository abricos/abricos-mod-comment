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
        ownerCreate: function(module, type, ownerid){
            var Owner = this.get('Owner');

            if (Y.Lang.isObject(module)){
                return new Owner(Y.merge(module, {appInstance: this}));
            }

            var owner = new Owner({appInstance: this});
            owner.set('module', module);
            owner.set('type', type);
            owner.set('ownerid', ownerid);
            return owner;
        },
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
        ATTRS: {
            Owner: {value: NS.Owner},
            Comment: {value: NS.Comment},
            CommentList: {value: NS.CommentList}
        },
        REQS: {
            commentList: {
                args: ['options'],
                attribute: false,
                type: 'modelList:CommentList',
                onResponse: function(commentList, srcData){
                    commentList.set('commentOwner', this.ownerCreate(srcData.owner));

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
                args: ['owner', 'reply'],
                type: 'model:Comment'
            },
            reply: {
                args: ['owner', 'reply'],
                type: 'model:Comment'
            }
        }
    });

};