/*
@version $Id$
@copyright Copyright (C) 2008 Abricos All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

/**
 * @module Comment
 * @namespace Brick.mod.comment
 */

var Component = new Brick.Component();
Component.requires = {
	yahoo: ['dom'],
	mod:[
		{name: 'user', files: ['permission.js']},
		{name: 'uprofile', files: ['viewer.js']}
    ]
};
Component.entryPoint = function(){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var NS = this.namespace,
		TMG = this.template, 
		API = NS.API;
	
	var UP = Brick.mod.uprofile;
	
	var buildTemplate = function(w, templates){
		var TM = TMG.build(templates), T = TM.data, TId = TM.idManager;
		w._TM = TM; w._T = T; w._TId = TId;
	};
	
	Brick.util.CSS.update(Brick.util.CSS['comment']['comment']);
	
	// загрузка роли пользователя
	var isViewRole = false,
		isWriteRole = false,
		isAdminRole = false;
	
	var loadRoles = function(callback){
		var P = Brick.Permission;
		P.load(function(){
			isViewRole = P.check('comment', '10') == 1;
			isWriteRole = P.check('comment', '20') == 1;
			isAdminRole = P.check('comment', '50') == 1;
			callback();
		});
	};
	
	var CommentBase = {};

	/**
	 * Конструктор дерева комментариев на странице.
	 * 
	 * @class Builder
	 * @constructor
	 * @param {HTMLElement|String} container HTML элемент или его идентификатор в Dom.
	 * @param {Integer} dbContentId Идентификатор из таблицы контента на сервере. 
	 * @param {Object} cfg Дополнительные параметры.
	 */
	var Builder = function(container, dbContentId, cfg){
		cfg = L.merge({
			'data': null,
			'readOnly': false,
			'lastView': -1,
			'manBlock': null,
			'debug': false,
			'onLoadComments': null
		}, cfg || {});
		this.init(container, dbContentId, cfg);
	};

	Builder.prototype = {
		
		/**
		 * Инициализация конструктора дерева комментариев.
		 * 
		 * @method init
		 * @param {HTMLElement|String} container HTML элемент или его идентификатор в Dom.
		 * @param {Integer} dbContentId Идентификатор из таблицы контента на сервере. 
		 * @param {Object} cfg Дополнительные параметры.
		 */
		init: function(element, dbContentId, cfg){
		
			/**
			 * HTML элемент в котором будет построено дерево комментариев.
			 * 
			 * @property element
			 * @type HTMLElement
			 */
			this.element = Dom.get(element);
			if (L.isNull(this.element)){ return; }
			
			/**
			 * Идентификатор из таблицы контента на сервере.
			 * 
			 * @property dbContentId
			 * @type Integer
			 */
			this.dbContentId = dbContentId;
			
			this.cfg = cfg;
			
			/**
			 * Идентификатор последнего комментария
			 * 
			 * @property lastCommentId
			 * @type Integer
			 */
			this.lastCommentId = 0;
			
			/**
			 * Кол-во комментариев.
			 * 
			 * @property count
			 * @type Integer
			 */
			this.count = 0;
			
			/**
			 * Открытый редактор комментария.
			 * 
			 * @property reply
			 * @type Brick.mod.comment.Reply
			 */
			this.reply = null;
			
			if (!CommentBase[dbContentId]){
				CommentBase[dbContentId] = {};
			}

			this.lastView = cfg['lastView'];
			this.readOnly = !isWriteRole ? true : cfg['readOnly'];
			this.manBlock = cfg['manBlock'];

			if (!L.isNull(this.manBlock)){
				this.manBlock.builder = this;
			}

			buildTemplate(this, 'panel,spam,list,comment,reply,replypanel');

			var TM = this._TM, TId = this._TId, 
				el = this.element,
				__self = this,
				getEl = function(name){ return Dom.get(TId['panel'][name]); };

			if (!L.isNull(cfg['data'])){
				// чтение данных
				var body = {}, data = cfg['data'];
				while(el.childNodes.length){
					var t = el.childNodes[0];
					if (t.childNodes.length == 3){
						body[t.childNodes[0].innerHTML] = t.childNodes[2].innerHTML;
					}
					el.removeChild(t);
				}
				var bdata = {};
				for (var i=0;i<data.length;i++){
					var di = data[i];
					di['bd'] = body[di['id']];
					bdata[di['id']] = di;
				}
				cfg['data'] = bdata;
			}
			
			el.innerHTML = TM.replace('panel', {'id': this.dbContentId, 'ttname': Brick.env.ttname });
			E.on(el, 'click', function(e){if (__self.onClick(E.getTarget(e))){ E.stopEvent(e);}});

			if (!this.readOnly){
				getEl('replyrootnone').style.display = 'none';
			}else{
				getEl('breplyroot').style.display = 'none';
			}
			
			// если данные по комментариям передают со страницы
			if (!L.isNull(cfg['data'])){
				this.render(cfg['data']);
			}else{
				this.refresh();
			}
		},
		
		/**
		 * Построить HTML код комментария из шаблона.
		 * 
		 * @method _getHTMLNode
		 * @private 
		 * @param {Object} di Данные комментария. 
		 */
		_getHTMLNode: function(di){
			return this._TM.replace('comment', {
				'unm': Brick.mod.uprofile.viewer.buildUserName(di),
				'uid': di['uid'],
				'avt': UP.avatar.get45(di),
				'ttname': Brick.env.ttname,
				'reply': (!this.readOnly ? this._T['reply'] : ""),
				'de':  Brick.dateExt.convert(di['de']),
				'id': di['id'],
				'bd': di['st']>0?T['spam']: di['bd']
			});
		},
		
		
		renderComment: function(di){
			var TM = this._TM, T = this._T, TId = this._TId;

			var pid = di['pid'];
			if (di['id']*1 > this.lastCommentId){ 
				this.lastCommentId = di['id']*1; 
			}
			var item = this._getHTMLNode(di);
			var child = this.count == 0 ? 
					TM.getEl('panel.list') : 
					Dom.get(TId['comment']['child']+'-'+pid);
					
			var list = Dom.get(TId['list']['id']+'-'+pid);
			if (L.isNull(list)){
				child.innerHTML = TM.replace('list', {
					'id': pid, 'list': item
				}); 
			}else{
				list.innerHTML += item;
			}
			this.count++;
		},
		
		getCommentElement: function(commentid){
			return Dom.get(this._TId['comment']['id']+'-'+commentid);
		},
		
		render: function(data){
			var GB = CommentBase[this.dbContentId],
				TM = this._TM, TId = this._TId;
			
			var firstRender = this.count == 0;
			
			// сбросить все флаги нового комментария
			for (var i in GB){
				var el = Dom.get(TId['comment']['meta']+'-'+i);
				Dom.removeClass(el, 'newreply');
			}
			// отобразить новые комментарии
			for (var i in data){
				var di = data[i];
				GB[di['id']] = di;
				this.renderComment(di);
			}
			
			// установить флаг нового комментария
			var mbData = {},
				userid = Brick.env.user.id;
			
			// для не авторизованного пользователя, новые только те, что определил браузер
			if (userid == 0 && !firstRender){
				for (var i in data){
					var di = data[i];
					if (userid != di['uid']){
						mbData[di['id']] = di;
						Dom.addClass(Dom.get(TId['comment']['meta']+'-'+i), 'newreply');
					}
				}
			}
			if (userid > 0 && this.lastView >= 0){
				for (var i in data){
					var di = data[i];
					if (userid != di['uid'] && di['id']*1 > this.lastView){
						mbData[di['id']] = di;
						Dom.addClass(Dom.get(TId['comment']['meta']+'-'+i), 'newreply');
					}
				}
			}
			this.lastView = this.lastCommentId*1;
			
			if (!L.isNull(this.manBlock)){
				this.manBlock.setNewComments(mbData);
			}
			this.renderCount();
		},
		
		/**
		 * Перерисовать кол-во комментариев
		 * 
		 * @method renderCount
		 */
		renderCount: function(){
			var span = Dom.get(this._TId['panel']['count']);
			span.innerHTML = "("+this.count+")";
		},
		
		/**
		 * Обработать клик мыши.
		 * 
		 * @method onClick
		 * @param {HTMLElement} el
		 * @return {Boolean}
		 */
		onClick: function(el){
			if (!L.isNull(this.reply)){
				if (this.reply.onClick(el)){ return true; }
			}
			var TId = this._TId;
			var tp = TId['panel'];
			switch(el.id){
			case tp['breplyroot']: this.showReply(0); return true;
			case tp['refresh']:
			case tp['refreshimg']: this.refresh(); return true;
			}
			
			var prefix = el.id.replace(/([0-9]+$)/, '');
			var numid = el.id.replace(prefix, "");
			
			if (prefix == TId['reply']['id']+'-'){
				this.showReply(numid); return true;
			}
			return false;
		},
		
		/**
		 * Написать комментарий.
		 * 
		 * @method showReply
		 * @param {Integer} parentCommentId Идентификатор комментария.
		 */
		showReply: function(parentCommentId){
			if (!L.isNull(this.reply)){
				this.reply.destroy();
			}
			this.reply = new Reply(this, parentCommentId);
		},
		
		/**
		 * Отправить комментарий
		 * @method send
		 * @param {String} text Текст комментария
		 * @returns
		 */
		send: function(parentCommentId, text, callback){
			parentCommentId = parentCommentId || 0;
			text = text || "";
			var __self = this;
			Brick.ajax('comment', {
				'data': {
					'do': 'list',
					'cid': this.dbContentId,
					'lid': this.lastCommentId,
					'pid': parentCommentId,
					'text': text
				},
				'event': function(request){
					if (L.isFunction(callback)){ callback(); }
					if (!request.data){ return; }
					r = request.data;
					__self.lastView = r.lastview;
					__self.render(r.list);
					if (L.isFunction(__self.cfg['onLoadComments'])){
						__self.cfg.onLoadComments();
					}
				}
			});
		},
		
		/**
		 * Запросить сервер обновить дерево комментариев, а именно, 
		 * подгрузить новые комментарии, если таковые имеются.
		 * 
		 * @method refresh
		 */
		refresh: function(callback){
			this.send(0, '', callback);
		}
	};
	
	NS.Builder = Builder;
	
	API.buildCommentTree = function(oArgs){
		loadRoles(function(){
			var b = new Builder(oArgs.container, oArgs.dbContentId, oArgs.config);
			if (L.isFunction(oArgs['instanceCallback'])){
				oArgs.instanceCallback(b);
			};
		});
	};
	
	/**
	 * Виджет "Написать комментарий"
	 * 
	 * @class Reply
	 * @constructor
	 * @param {Brick.mod.comment.Builder} owner Конструктор дерева комментариев.
	 * @param {Integer} parentCommentId Идентификатор комментария родителя, в таблице 
	 * комментариев на сервера, на который будет дан ответ, если 0, то это будет 
	 * первый комментарий.  
	 */
	var Reply = function(owner, parentCommentId){
		parentCommentId = parentCommentId*1 || 0;
		this.init(owner, parentCommentId);
	};

	Reply.prototype = {
		
		/**
		 * Конструктор дерева комментариев.
		 * 
		 * @property owner
		 * @type Brick.mod.comment.Builder
		 */
		owner: null,
		
		/**
		 * Идентификатор комментария родителя, в таблице 
		 * комментариев на сервера, на который будет дан ответ, если 0, то это будет 
		 * первый комментарий.
		 * 
		 * @property parentCommentId
		 * @type Integer
		 */
		parentCommentId: 0,
		
		/**
		 * Редактор комментария.
		 * 
		 * @property editor
		 * @type Brick.widget.Editor
		 */
		editor: null,
		
		/**
		 * Инициализировать редактор.
		 * 
		 * @method init
		 * @param {Brick.mod.comment.Builder} owner Конструктор дерева комментариев.
		 * @param {Integer} parentCommentId Идентификатор комментария родителя.
		 */
		init: function(owner, parentCommentId){
			this.owner = owner;
			this.parentCommentId = parentCommentId;
			
			var TM = this._TM = owner._TM,
				T = this._T = owner._T,
				TId = this._TId = owner._TId;
			
			
			if (parentCommentId == 0){
				this.contbutton = TM.getEl('panel.replycont');
				this.panel = TM.getEl('panel.reply');
			}else{
				this.contbutton = Dom.get(TId['reply']['contbtn']+'-'+parentCommentId);
				this.panel = Dom.get(TId['reply']['reply']+'-'+parentCommentId);
			}
			this.contbutton.style.display = 'none';
			
			var __self = this;
			this.panel.innerHTML = T['replypanel'];
			Brick.Component.API.fireFunction('sys', 'editor', function(){
				var Editor = Brick.widget.Editor;
				__self.editor = new Editor(TId['replypanel']['editor'], {
					'mode': Editor.MODE_VISUAL,
					'toolbar': Editor.TOOLBAR_MINIMAL
					//,
					// 'fileManager': false,
					//'configButtons': false
				});
			});
		},
		
		/**
		 * Закрыть и разрушить панель.
		 * 
		 * @method destroy
		 */
		destroy: function(){
			this.editor.destroy();
			this.contbutton.style.display = "";
			Brick.elClear(this.panel);
			this.owner.reply = null;
		},
		
		/**
		 * Просмотреть комментарий как он будет выглядеть после отправки.
		 * 
		 * @method preview
		 */
		preview: function(){
			var __self = this, TM = this._TM;
			
			this._showHideLoading(true);
			var text = this.editor.getContent();

			Brick.ajax('comment', {
				'data': {
					'do': 'preview',
					'text': text
				},
				'event': function(request){
					__self._showHideLoading(false);
					var comment = request.data;
					if (comment && comment['text']){
						TM.getEl('replypanel.preview').innerHTML = comment['text'];
					}
				}
			});
		},
		
		_showHideLoading: function(show){
			var TM = this._TM;
			var el = function(n){return TM.getEl('replypanel.'+n);};
			el('buttons').style.display = show ? 'none' : '';
			el('wait').style.display = show ? '' : 'none';
		},
		
		/**
		 * Отправить комментарий.
		 * 
		 * @method send
		 */
		send: function(){
			var __self = this;
			this._showHideLoading(true);
			this.owner.send(this.parentCommentId, this.editor.getContent(), function(){
				__self.destroy();
			});
		},
		
		/**
		 * Обработать клик мыши.
		 * 
		 * @method onClick
		 * @param {HTMLElement} el
		 * @return {Boolean}
		 */
		onClick: function(el){
			var tp = this._TId['replypanel'];
			switch(el.id){
			case tp['bcancel']: this.destroy(); return true;
			case tp['bsend']: this.send();	return true;
			case tp['bpreview']: this.preview(); return true;
			}
			return false;
		}
	};
	
	NS.Reply = Reply;
	
	var ManagerBlockWidget = function(container, posCSS, scrollToComment){
		container = Dom.get(container);
		this.init(container, posCSS, scrollToComment);
	};
	ManagerBlockWidget.prototype = {
		init: function(container, posCSS, scrollToComment){
			this.builder = null;
			this.data = {};
			if (L.isFunction(scrollToComment)){
				this.scrollToComment = scrollToComment;
			}
			
			buildTemplate(this, 'manblock');
			var TM = this._TM;
			container.innerHTML = TM.replace('manblock', {
				'pos': posCSS
			});
			var el = TM.getEl('manblock.id');
			Dom.setStyle(el, 'opacity', 0.5);
			E.on(el, 'mouseover', function(){ Dom.setStyle(el, 'opacity', 1); });
			E.on(el, 'mouseout',  function(){ Dom.setStyle(el, 'opacity', 0.5); });
			var __self = this;
			E.on(container, 'click', function(e){if (__self.onClick(E.getTarget(e))){ E.stopEvent(e);}});
		},
		destroy: function(){ },
		onClick: function(el){
			var tp = this._TId['manblock'];
			switch(el.id){
			case tp['brefresh']: this.refresh(); return true;
			case tp['bcount']: 
				return this._scrollToComment();
			}
			return false;
		},
		scrollToComment: function(commentid, builder){
			return false;
		},
		_scrollToComment: function(){
			
			var ndata = {}, data = this.data;
			var firstid = 0;
			for (var i in data){
				var di = data[i];
				if (firstid == 0){
					firstid = i;
				} else {
					ndata[i] = di;
				}
			}
			var stopEvent = this.scrollToComment(firstid, this.builder);
			
			this.setNewComments(ndata);
			return stopEvent;
		},
		refresh: function(){
			if (L.isNull(this.builder)){ return; }
			var btn = this._TM.getEl('manblock.brefresh');
			Dom.replaceClass(btn, 'button', 'button-loading');
			this.builder.refresh(function(){
				Dom.replaceClass(btn, 'button-loading', 'button');
			});
		},
		setNewComments: function(data){
			data = data || {};
			var count = 0, firstid = 0;
			
			for (var i in data){ 
				if (firstid == 0){
					firstid = i;
				}
				count++; 
			}
			this._TM.getEl('manblock.newblock').style.display = count > 0 ? '' : 'none';
			var elBC = this._TM.getEl('manblock.bcount');
			elBC.innerHTML = count;
			if (count > 0){
				elBC.href = "#"+this.builder._TId['comment']['id']+'-'+firstid;
			}
			this.data = data;
		}
	};
	
	NS.ManagerBlockWidget = ManagerBlockWidget;
};
