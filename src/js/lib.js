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
                    console.log(arguments);
                }
            }
        },
        ATTRS: {
            Comment: {value: NS.Comment},
            CommentList: {value: NS.CommentList}
        }
    });

};