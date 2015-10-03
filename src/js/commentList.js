var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.CommentListWidget = Y.Base.create('commentListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);
            var ownerModule = this.get('ownerModule'),
                ownerType = this.get('ownerType'),
                ownerid = this.get('ownerid');

            this.get('appInstance').commentList(ownerModule, ownerType, ownerid, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('commentList', result.commentList);
                }
                this.renderCommentList();
            }, this);
        },
        renderCommentList: function(){
            var commentList = this.get('commentList');
            if (!commentList){
                return;
            }
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {
                value: 'widget'
            },
            ownerModule: {},
            ownerType: {},
            ownerid: {},
            commentList: {}
        }
    });

};