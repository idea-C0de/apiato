<?php

/**
 * @apiGroup           Settings
 * @apiName            createSetting
 *
 * @api                {POST} /v1/settings Create Setting
 * @apiDescription     Create a new setting for the application
 *
 * @apiVersion         1.0.0
 * @apiPermission      none
 *
 * @apiParam           {String}  parameters here..
 *
 * @apiSuccessExample  {json}  Success-Response:
 * HTTP/1.1 200 OK
{
    "data": {
        "object": "Setting",
        "id": "aadfa72342sa",
        "key": "hello",
        "value": "world"
    },
    "meta": {
        "include": [],
        "custom": []
    }
}
 */

$router->post('settings', [
    'as' => 'API_Settings_createSetting',
    'uses'  => 'Controller@createSetting',
    'middleware' => [
      'auth:api',
    ],
]);
