var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['tree.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var UID = Brick.env.user.id | 0;

    var UIManager = function(){
        if (UID === 0){
            return;
        }
        var instance = this;
        NS.initApp({
            initCallback: function(){
                instance.init();
            }
        });
    };
    UIManager.prototype = {
        init: function(){
            Y.Node.all('.aw-comment.commentTree').each(function(node){
                var staticNode = node.one('.staticNode'),
                    dinamicNode = node.one('.dinamicNode'),
                    ownerModule = node.getData('module'),
                    ownerType = node.getData('type'),
                    ownerid = node.getData('ownerid') | 0;

                if (!ownerModule || !ownerType || !ownerid
                    || !staticNode || !dinamicNode){
                    return;
                }

                new NS.CommentTreeWidget({
                    boundingBox: dinamicNode,
                    srcBodyData: staticNode,
                    readOnly: false,
                    commentOwner: {
                        module: ownerModule,
                        type: ownerType,
                        ownerid: ownerid
                    },
                    onInitCallback: function(err){
                        if (err){
                            return;
                        }
                        dinamicNode.removeClass('hide');
                        staticNode.remove();
                    }
                });
            }, this);
        }
    };

    NS.UIManager = UIManager;
    new NS.UIManager();
};