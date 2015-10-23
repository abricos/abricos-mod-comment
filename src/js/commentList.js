var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var ExtCommentList = function(){
    };
    ExtCommentList.NAME = 'extCommentList';
    ExtCommentList.ATTRS = {
        ownerModule: {validator: Y.Lang.isString},
        ownerType: {validator: Y.Lang.isString},
        ownerid: {validator: Y.Lang.isNumber, value: 0},
        parentWidget: {},
        rootWidget: {
            readOnly: true,
            getter: function(){
                if (this._rootWidgetValue){
                    this._rootWidgetValue;
                }
                var root = this,
                    parentWidget;
                while (parentWidget = root.get('parentWidget')){
                    root = parentWidget;
                }
                return this._rootWidgetValue = root;
            }
        },
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
        },
        readOnly: {value: true}
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
        _renderCommentList: function(commentList){
            var isNewCommentList = false;
            if (commentList){
                isNewCommentList = true;
            }
            commentList = commentList || this.get('commentList');

            var tp = this.template,
                ws = this._widgets,
                readOnly = this.get('readOnly'),
                commentid = this.get('commentid'),
                parentWidget = this;

            commentList.each(function(comment){
                if (comment.get('parentid') !== commentid){
                    return;
                }

                var w = new NS.CommentItemWidget({
                    boundingBox: tp.append('list', '<div></div>'),
                    ownerModule: this.get('ownerModule'),
                    ownerType: this.get('ownerType'),
                    ownerid: this.get('ownerid'),
                    parentWidget: parentWidget,
                    commentid: comment.get('id'),
                    commentList: commentList,
                    readOnly: readOnly
                });
                ws[ws.length] = w;
            }, this);

            if (isNewCommentList){
                this.each(function(w){
                    w._renderCommentList(commentList);
                }, this);
            }

            tp.toggleView(!readOnly, 'replyButton');
        },
        each: function(fn, context){
            var ws = this._widgets;
            for (var i = 0; i < ws.length; i++){
                fn.call(context || this, ws[i]);
            }
        },
        replyClose: function(){
            this.each(function(w){
                w.replyClose();
            }, this);

            if (!this._replyEditor){
                return;
            }
            var tp = this.template;

            tp.setHTML('replyPanel', '');
            tp.toggleView(false, 'replyPanel', 'replyButton');

            this._replyEditor.destroy();
            delete this._replyEditor;
        },
        replyShow: function(){
            this.get('rootWidget').replyClose();

            var tp = this.template;

            tp.setHTML('replyPanel', tp.replace('reply'));
            tp.toggleView(true, 'replyPanel', 'replyButton');

            this._replyEditor = new SYS.Editor({
                appInstance: this.get('appInstance'),
                srcNode: tp.gel('reply.editor'),
                content: '',
                toolbar: SYS.Editor.TOOLBAR_MINIMAL
            });
        },
        replyToJSON: function(){
            if (!this._replyEditor){
                return null;
            }
            return {
                parentid: this.get('commentid'),
                body: this._replyEditor.get('content')
            };
        },
        replySend: function(){
            var ownerModule = this.get('ownerModule'),
                ownerType = this.get('ownerType'),
                ownerid = this.get('ownerid'),
                reply = this.replyToJSON();

            this.set('waiting', true);
            this.get('appInstance').reply(ownerModule, ownerType, ownerid, reply, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.replyClose();
                }
            }, this);
        },
        replyPreview: function(){
            var ownerModule = this.get('ownerModule'),
                ownerType = this.get('ownerType'),
                ownerid = this.get('ownerid'),
                reply = this.replyToJSON();

            this.set('waiting', true);
            this.get('appInstance').replyPreview(ownerModule, ownerType, ownerid, reply, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.setPreview(result.replyPreview);
                }
            }, this);
        },
        setPreview: function(comment){
            var tp = this.template;
            tp.show('reply.preview');
            tp.setHTML('reply.preview', comment.get('body'));
        },
        onClick: function(e){
            switch (e.dataClick) {
                case 'replyShow':
                    this.replyShow();
                    return true;
                case 'replyClose':
                    this.replyClose();
                    return true;
                case 'replyPreview':
                    this.replyPreview();
                    return true;
                case 'replySend':
                    this.replySend();
                    return true;
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

            tp.setHTML({
                aViewName: user.get('viewName'),
                date: Brick.dateExt.convert(comment.get('dateline')),
                body: comment.get('body')
            });

            tp.one('avatarSrc').set('src', user.get('avatarSrc45'));

            this._renderCommentList();
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'item,reply'}
        }
    });

    NS.CommentListWidget = Y.Base.create('commentListWidget', SYS.AppWidget, [
        NS.ExtCommentList
    ], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            var ownerModule = this.get('ownerModule'),
                ownerType = this.get('ownerType'),
                ownerid = this.get('ownerid')

            appInstance.commentList(ownerModule, ownerType, ownerid, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('commentList', result.commentList);
                }
                this.renderCommentList();

                appInstance.on('appResponses', this._onAppResponses, this);
            }, this);
        },
        destructor: function(){
            this.get('appInstance').detach('appResponses', this._onAppResponses, this);
        },
        _onAppResponses: function(e){
            if (e.err || !e.result.commentList){
                return;
            }
            var commentList = e.result.commentList;
            if (commentList.get('ownerModule') !== this.get('ownerModule')
                || commentList.get('ownerType') !== this.get('ownerType')
                || commentList.get('ownerid') !== this.get('ownerid')){
                return;
            }
            this.renderCommentList(commentList);
        },
        renderCommentList: function(newCommentList){
            var tp = this.template,
                commentList = this.get('commentList'),
                readOnly = this.get('readOnly'),
                count = commentList.size();

            if (newCommentList){
                count += newCommentList.size();
            }

            tp.setHTML({
                count: count
            });

            this._renderCommentList(newCommentList);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,reply'}
        }
    });

};