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
        ATTRS: {
            ownerList: {
                readOnly: true,
                getter: function(){
                    if (!this._ownerListAttr){
                        this._ownerListAttr = new NS.OwnerList({appInstance: this});
                    }
                    return this._ownerListAttr;
                }
            },
            Owner: {value: NS.Owner},
            Comment: {value: NS.Comment},
            CommentList: {value: NS.CommentList},
            Statistic: {value: NS.Statistic},
            StatisticList: {value: NS.StatisticList}
        },
        REQS: {
            commentList: {
                args: ['options'],
                attribute: false,
                type: 'modelList:CommentList',
                onResponse: function(commentList, srcData){
                    var ownerList = this.get('ownerList'),
                        owner = ownerList.getOwner(srcData.owner);

                    owner.set('userview', srcData.userview);

                    commentList.set('commentOwner', owner);
                    commentList.set('userview', srcData.userview);

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