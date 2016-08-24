var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    var isOwner = function(val){
        if (!val){
            return false;
        }
        if (val.module && val.type && val.ownerid){
            return true;
        }
        if (!Y.Lang.isFunction(val.get)){
            return false;
        }
        return true;
    };

    NS.Owner = Y.Base.create('owner', SYS.AppModel, [], {
        structureName: 'Owner',
        compare: function(val){
            if (!NS.Owner.isOwner(val)){
                return false;
            }
            return val.get('module') === this.get('module')
                && val.get('type') === this.get('type')
                && val.get('ownerid') === this.get('ownerid');
        }
    }, {
        ATTRS: {
            id: {
                readOnly: true,
                getter: function(){
                    return this.get('module') + '|'
                        + this.get('type') + '|'
                        + this.get('ownerid');
                }
            },
            userview: {
                value: 0,
                setter: function(val){
                    this._userviewAttr = Math.max(val | 0, this._userviewAttr | 0);
                    return this._userviewAttr;
                }
            }
        },
        ATTRIBUTE: {
            validator: isOwner,
            setter: function(val){
                if (val.module && val.type && val.ownerid){
                    return this.get('appInstance').get('ownerList').getOwner(val.module, val.type, val.ownerid);
                }
                return val;
            }
        },
        isOwner: isOwner
    });

    NS.OwnerList = Y.Base.create('ownerList', SYS.AppModelList, [], {
        appItem: NS.Owner,
        getOwner: function(module, type, ownerid){
            var app = this.appInstance,
                Owner = app.get('Owner'),
                id;

            if (Y.Lang.isObject(module)){
                var tempOwner = new Owner(Y.merge(module, {appInstance: app}));
                id = tempOwner.get('id');
            } else {
                id = module + '|' + type + '|' + ownerid;
            }

            var owner = this.getById(id);
            if (owner){
                return owner;
            }

            if (Y.Lang.isObject(module)){
                owner = new Owner(Y.merge(module, {appInstance: app}));
            } else {
                owner = new Owner({
                    module: module,
                    type: type,
                    ownerid: ownerid,
                    appInstance: app
                });
            }

            this.add(owner);

            return owner;
        },
    });


    NS.Statistic = Y.Base.create('statistic', SYS.AppModel, [], {
        structureName: 'Statistic'
    }, {
        ATTRS: {
            lastUser: {}
        }
    });

    NS.StatisticList = Y.Base.create('statisticList', SYS.AppModelList, [], {
        appItem: NS.Statistic
    });

    NS.Comment = Y.Base.create('comment', SYS.AppModel, [], {
        structureName: 'Comment'
    }, {
        ATTRS: {
            user: {}
        }
    });

    NS.CommentList = Y.Base.create('commentList', SYS.AppModelList, [], {
        appItem: NS.Comment
    }, {
        ATTRS: {
            commentOwner: NS.Owner.ATTRIBUTE,
            userview: {value: 0}
        }
    });
};