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
    });

    NS.StatisticList = Y.Base.create('statisticList', SYS.AppModelList, [], {
        appItem: NS.Statistic
    });
};