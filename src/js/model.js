var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

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

    NS.Comment = Y.Base.create('statistic', SYS.AppModel, [], {
        structureName: 'Comment'
    }, {
        ATTRS: {
            user: {}
        }
    });

    NS.CommentList = Y.Base.create('statisticList', SYS.AppModelList, [], {
        appItem: NS.Comment
    });
};