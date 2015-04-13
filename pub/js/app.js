
var cotyaIDE = angular.module(
    'cotyaIDE',
    [
        'ez.fileTree'
    ]
);

cotyaIDE.constant('EzFileTreeConfig', {
    enableChecking: false, // show a checkbox beside each file
    enableFolderSelection: false, // allow folders to be selected
    enableFileSelection: true, // allow files to be selected
    multiSelect: false, // allow multiple files to be selected
    recursiveSelect: false, // recursively select a folders children
    recursiveUnselect: true, // recursively unselect a folders children
    icons: {
        chevronRight: 'fa fa-chevron-right',
        chevronDown: 'fa fa-chevron-down',
        folder: 'fa fa-folder',
        file: 'fa fa-file'
    },
    childrenField: 'children', // the field name to recurse
    idField: 'id', // the files id field
    isFolder: function(file) { // function that checks if file is a folder
        return file.type === 'folder';
    }
})

cotyaIDE.controller('StatsController', function ($scope, $http) {
    $scope.stats = {
        memoryusage: NaN
    };
    
    var bytesToSize = function(bytes) {if(bytes == 0) return '0 Byte';
        var k = 1000;
        var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return (bytes / Math.pow(k, i)).toPrecision(3) + ' ' + sizes[i];
    };
    var refresh = function(){
        $http.get('memory').success(function(data) {
            $scope.stats.memoryusage  = bytesToSize(data);
        });
    };
    refresh();
    setInterval(refresh , 10000);
});

cotyaIDE.controller('FileTreeController', function($scope,$http,$rootScope) {

    //$scope.tree = {};
    //$scope.tree._activeFiles = {};

    $scope.$rootScope = $rootScope;

    $scope.toggle = function (event, data) {
        console.log(event,data);
    };
    $scope.$on('ez-file-tree.select', function(e, data) {
        console.log('file checked' + data.id);
        e.currentScope.$rootScope.openFile(data.id);
    });

    var refresh = function(){
        $http.get('ide/filetree').success(function(data) {
            $scope.folders  = data;
        });
    };
    $scope.refresh = refresh;
    refresh();
    
});

cotyaIDE.controller('EditorController', function($scope,$http,$element,$rootScope) {

    var editor = ace.edit("editor");
    editor.setReadOnly(1);
    
    var openFile;
    var openFileContent;
    
    var editorSaveButton = $element.children()[0];

    editor.on("change", function(e){
        if($scope.fileNeedsSave()){
            angular.element(editorSaveButton).addClass('dirty');
        }else{
            angular.element(editorSaveButton).removeClass('dirty');
        }
    });
    
    $rootScope.openFile = function(filepath){
        console.log('open file:'+filepath);

        $http.get('ide/file',{ params: {file: filepath} }).success(function(data) {
            openFile = filepath;
            openFileContent = data;
            editor.getSession().setMode("ace/mode/php");
            editor.setValue(data);
            editor.gotoLine(0);
            editor.setReadOnly(0);
        });
    };
    
    $scope.save = function(){
        $http.post('ide/file', {
            file: openFile,
            content: editor.getValue()
        });
        openFileContent = editor.getValue();
    };
    
    $scope.fileNeedsSave = function(){
        var result = false;
        if(editor.getValue() && openFileContent){
            result = editor.getValue().trim()!=openFileContent.trim();
        }else{
            result = false;
        }
        return result;
    };
    
});

cotyaIDE.filter('stringify', function() {
    function getSerialize (fn, decycle) {
        var seen = [], keys = [];
        decycle = decycle || function(key, value) {
            return '[Parent REFERENCE]: ' + value.id;
        };
        return function(key, value) {
            var ret = value;
            if (typeof value === 'object' && value) {
                if (seen.indexOf(value) !== -1)
                    ret = decycle(key, value);
                else {
                    seen.push(value);
                    keys.push(key);
                }
            }
            if (fn) ret = fn(key, ret);
            return ret;
        }
    }

    function getPath (value, seen, keys) {
        var index = seen.indexOf(value);
        var path = [ keys[index] ];
        for (index--; index >= 0; index--) {
            if (seen[index][ path[0] ] === value) {
                value = seen[index];
                path.unshift(keys[index]);
            }
        }
        return '~' + path.join('.');
    }

    function stringify(obj, fn, spaces, decycle) {
        return JSON.stringify(obj, getSerialize(fn, decycle), spaces);
    }

    stringify.getSerialize = getSerialize;

    return function(ob) {
        return stringify(ob, undefined, 4);
    };
});

/*
composerWebuiApp.controller('TabsCtrl', function ($scope) {
    $scope.tabs = [{
        title: 'Overview',
        url: 'overview.tpl.html'
    }];

    $scope.currentTab = 'overview.tpl.html';

    $scope.onClickTab = function (tab) {
        $scope.currentTab = tab.url;
    };

    $scope.isActiveTab = function(tabUrl) {
        return tabUrl == $scope.currentTab;
    }
});

*/
