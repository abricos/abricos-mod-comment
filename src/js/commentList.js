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

    var ExtCommentList = function(){
    };
    ExtCommentList.prototype = {
        initializer: function(){
            this._widgets = [];
        },
        destructor: function(){
            var ws = this._widgets;
            for (var i = 0; i < ws.length; i++){
                ws[i].destroy();
            }
        },
        renderCommentList: function(){
            var tp = this.template,
                ws = this._widgets,
                commentList = this.get('commentList'),
                commentid = this.get('commentid');

            commentList.each(function(comment){
                if (comment.get('parentid') !== commentid){
                    return;
                }
                console.log(comment.toJSON());
                var w = new NS.CommentItemWidget({
                    srcNode: tp.append('list', '<div></div>'),
                    ownerModule: this.get('ownerModule'),
                    ownerType: this.get('ownerType'),
                    ownerid: this.get('ownerid'),
                    commentid: comment.get('id'),
                    commentList: commentList
                });
                ws[ws.length] = w;
            }, this);
        }
    };
    ExtCommentList.NAME = 'extCommentList';
    ExtCommentList.ATTRS = {
        ownerModule: {validator: Y.Lang.isString},
        ownerType: {validator: Y.Lang.isString},
        ownerid: {validator: Y.Lang.isNumber, value: 0},
        commentList: {},
        commentid: {
            validator: Y.Lang.isNumber,
            value: 0
        },
        comment: {
            readOnly: true,
            getter: function(){
                return this.get('commentList').getById(this.get('commentid'));
            }
        }
    };
    NS.ExtCommentList = ExtCommentList;


    NS.CommentItemWidget = Y.Base.create('commentItemWidget', SYS.AppWidget, [
        NS.ExtCommentList
    ], {
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                comment = this.get('comment'),
                user = comment.get('user');

            console.log(comment.toJSON());

            tp.setHTML({
                aViewName: user.get('viewName'),
                date: Brick.dateExt.convert(comment.get('dateline')),
                body: comment.get('body')
            });

            tp.one('avatarSrc').set('src', user.get('avatarSrc45'));

        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'item'}
        }
    });

    NS.CommentListWidget = Y.Base.create('commentListWidget', SYS.AppWidget, [
        NS.ExtCommentList
    ], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            this.get('appInstance').commentList(this.get('ownerModule'), this.get('ownerType'), this.get('ownerid'), function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('commentList', result.commentList);
                }
                this.renderCommentList();
            }, this);
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
        }
    });

};