var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'urating', files: ['vote.js']},
        {name: 'sys', files: ['editor.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys,
        UID = Brick.env.user.id | 0;

    var ExtCommentTree = function(){
    };
    ExtCommentTree.NAME = 'extCommentList';
    ExtCommentTree.ATTRS = {
        commentOwner: NS.Owner.ATTRIBUTE,
        parentWidget: {},
        rootWidget: {
            readOnly: true,
            getter: function(){
                if (this._rootWidgetValue){
                    return this._rootWidgetValue;
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
    ExtCommentTree.prototype = {
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

                var w = new NS.CommentTreeWidget.ItemWidget({
                    boundingBox: tp.append('list', '<div></div>'),
                    parentWidget: parentWidget,
                    commentOwner: this.get('commentOwner'),
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
        setReadOnly: function(readOnly){
            this.template.toggleView(!readOnly, 'replyButton');
            this.each(function(w){
                w.setReadOnly(readOnly);
            }, this);
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
            var owner = this.get('commentOwner').toJSON(),
                reply = this.replyToJSON();

            this.set('waiting', true);
            this.get('appInstance').reply(owner, reply, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.replyClose();
                }
            }, this);
        },
        replyPreview: function(){
            var owner = this.get('commentOwner').toJSON(),
                reply = this.replyToJSON();

            this.set('waiting', true);
            this.get('appInstance').replyPreview(owner, reply, function(err, result){
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
    NS.ExtCommentTree = ExtCommentTree;

    NS.CommentTreeWidget = Y.Base.create('commentListWidget', SYS.AppWidget, [
        NS.ExtCommentTree
    ], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            var owner = this.get('commentOwner'),
                options = owner.toJSON();

            options.notBody = !!this.get('srcBodyData');
            appInstance.commentList(options, this._onLoadCommentList, this);
        },
        destructor: function(){
            this.get('appInstance').detach('appResponses', this._onAppResponses, this);
        },
        _onAppResponses: function(e){
            if (e.err || !e.result.commentList){
                return;
            }
            var commentList = e.result.commentList;

            if (!this.get('commentOwner').compare(commentList.get('commentOwner'))){
                return;
            }
            this.renderCommentList(commentList);
        },
        _fillCommentBody: function(){
            var srcBodyData = this.get('srcBodyData');
            if (!srcBodyData){
                return;
            }
            var commentList = this.get('commentList'),
                comment;

            srcBodyData.all('.commentBodyData').each(function(node){
                comment = commentList.getById(node.getData('id'));
                if (!comment){
                    return;
                }
                comment.set('body', node.getHTML());
            }, this);
        },
        _onLoadCommentList: function(err, result){
            this.set('waiting', false);
            if (err){
                return;
            }
            this.set('commentList', result.commentList);

            this._fillCommentBody();
            this.renderCommentList();

            this.get('appInstance').on('appResponses', this._onAppResponses, this);
            this.on('readOnlyChange', function(e){
                this.setReadOnly(e.newVal);
            }, this);

            var callbackfn = this.get('onInitCallback');
            if (Y.Lang.isFunction(callbackfn)){
                callbackfn.call(this, err);
            }
        },
        renderCommentList: function(newCommentList){
            var tp = this.template,
                commentList = this.get('commentList'),
                count = commentList.size();

            if (newCommentList){
                count += newCommentList.size();
            }

            tp.setHTML({count: count});

            this._renderCommentList(newCommentList);
            if (newCommentList){
                newCommentList.each(function(comment){
                    commentList.add(comment);
                }, this);
            }
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,reply'},
            onInitCallback: {},
            srcBodyData: {
                validator: Y.one
            }
        }
    });

    NS.CommentTreeWidget.ItemWidget = Y.Base.create('itemWidget', SYS.AppWidget, [
        NS.ExtCommentTree
    ], {
        buildTData: function(){
            var comment = this.get('comment'),
                commentid = comment.get('id'),
                userview = this.get('commentList').get('userview');

            return {
                isNewClass: comment.get('userid') !== UID && commentid > userview
                    ? 'newComment' : ''
            };
        },
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                owner = this.get('commentOwner'),
                comment = this.get('comment'),
                voting = comment.get('voting'),
                user = comment.get('user');

            owner.set('userview', comment.get('id'));

            tp.setHTML({
                aViewName: user.get('viewName'),
                date: Brick.dateExt.convert(comment.get('dateline')),
                body: comment.get('body')
            });

            tp.one('avatarSrc').set('src', user.get('avatarSrc45'));

            if (voting){
                this.votingWidget = new Brick.mod.urating.VotingWidget({
                    boundingBox: this.gel('voting'),
                    voting: voting
                });
            }

            this._renderCommentList();
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'item,reply'}
        }
    });


};