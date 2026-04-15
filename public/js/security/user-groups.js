app.controller('ngUserGroupController', function ($http, transformRequestAsFormPost) {
    var $ctrl = this;

    $ctrl.checkGroup = function ($groupId, $userId) {
        var $url = Routing.generate('group_check');
        var $params = {
            'userId': $userId,
            'groupId': $groupId
        };

        $http.get(
            $url,
            {
                params: $params
            }
        ).then(function (response) {
            console.log(response.data);
        });
    };

    $ctrl.checkRole = function ($roleId, $groupId) {
        var $url = Routing.generate('role_check');
        var $params = {
            'roleId': $roleId,
            'groupId': $groupId
        };

        $http.get(
            $url,
            {
                params: $params
            }
        ).then(function (response) {
            console.log(response.data);
        });
    };
});