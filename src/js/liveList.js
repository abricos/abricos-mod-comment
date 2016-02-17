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

    NS.CommentLiveListWidget = Y.Base.create('commentLiveListWidget', SYS.AppWidget, [
    ], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            var options = {
                module: this.get('ownerModule'),
                module: this.get('ownerType'),
            };

            appInstance.commentLiveList(options, function(err, result){
                this.set('waiting', false);
                console.log(result);
            }, this);
        },
        destructor: function(){
            this.get('appInstance').detach('appResponses', this._onAppResponses, this);
        },
        _onAppResponses: function(e){
            /*
            if (e.err || !e.result.commentList){
                return;
            }
            var commentList = e.result.commentList;

            if (!this.get('commentOwner').compare(commentList.get('commentOwner'))){
                return;
            }
            this.renderCommentList(commentList);
            /**/
        },
        renderCommentList: function(newCommentList){

        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,reply'},
            ownerModule: {},
            ownerType: {}
        }
    });


};