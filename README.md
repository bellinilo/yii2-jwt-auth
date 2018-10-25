# yii2-jwt-auth
Yii2 JWT Auth

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist kakadu-dev/yii2-jwt-auth "@dev"
```

or add

```
"kakadu-dev/yii2-jwt-auth": "@dev"
```

to the require section of your `composer.json` file.

Usage
-----

Once the extension is installed, simply use it in your code by:

Add this package migration namespace, to you console config (console/config/main.php):

```php
return [
    'components' => [
        'migrate' => [
            'class'               => yii\console\controllers\MigrateController::class,
            // set false if you use namespaces
            'migrationPath'       => '@console/migrations',
            'migrationNamespaces' => [
                // ...
                'MP\Yii2JwtAuth\migrations',
            ],
        ],
    ],
];
```

Configure api tokens component (e.g. common/config/main.php):

```php
return [
    'components' => [
        'apiTokens'      => [
            'class'     => \MP\Yii2JwtAuth\ApiTokenService::class,
            'secretKey' => '', // set in main-local.php
            'issuer'    => 'you-domain-name', // or yii-params.domain
            'audience'  => ['you-domain-name'], // or yii-params.domain
            'seamless_login' => false,
        ],
    ],
];
```

All values in _issuer_, _audience_, _audienceSecrets_ which contain _yii-params.param-name_ will be converted to Yii::$app->params['param-name']

Now, after user registration, create JWT tokens and add their in response headers. 
Also add an action to update tokens.  
E.g.:
```php
class AuthController extends yii\rest\Controller
{
    public function actionSignUp()
    {
        // After create user $newUser
        // Same actions for login url
        $tokens = \Yii::$app->apiTokens->create($newUser->id, ['someField' => 'someValue']);
        
        MP\Yii2JwtAuth\JwtBearerAuth::addJwtToHeader(\Yii::$app->response, $tokens);
    }
    
    public function actionSignIn()
    {
        // After verify user login and password
    
        $tokens = \Yii::$app->apiTokens->create($user->id, ['someField' => 'someValue']);
        
        MP\Yii2JwtAuth\JwtBearerAuth::addJwtToHeader(\Yii::$app->response, $tokens);
    }
    
    /**
     * Autologin, if access token expired and refresh token not expired.
     * This action needed only if 'seamless_login' set to false.
     */
    public function actionRefreshTokens()
    {
        // Get from post or headers or ...
        $accessToken = Yii::$app->request->post('access_token');
        $refreshToken = Yii::$app->request->post('refresh_token');
    
        // Convert to jwt token model
        $jwtAccessToken  = \Yii::$app->apiTokens->getJwtToken($accessToken);
        $jwtRefreshToken = \Yii::$app->apiTokens->getJwtToken($refreshToken);
    
        // Renew
        $newTokens = \Yii::$app->apiTokens->renewJwtToken($jwtAccessToken, $jwtRefreshToken);
        
        MP\Yii2JwtAuth\JwtBearerAuth::addJwtToHeader(\Yii::$app->response, $newTokens);
    }
}
```

And finally add Jwt Auth to secure controller:
```php
class SecureController extends yii\rest\Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'class' => MP\Yii2JwtAuth\JwtBearerAuth::class,
            ],
            'access'        => [
                'class' => AccessControl::class,
                'rules' => [
                   ...
                ],
            ],
        ]);
    }
}
```


**Procedure:**    

 - a. seamless_login is false    
           1. Register, get access and refresh token and save their on client side.  
           2. Use only access token for request to security endpoint.  
           3. After access token expired, you get 401 Unauthorized exception  
           _4. Use expire access and not expire refresh token to get new tokens._ (/refresh-token  url)  
           5. If refresh token expire, go to sign in  
   
 - b. seamless_login is true   
           1. Register, get access and refresh token and save their on client side.  
           2. Use only access token for request to security endpoint.  
           3. After access token expired, you get 401 Unauthorized exception  
           _4. Repeat request use expire access and not expire refresh token to get new tokens._ (/same url)  
           5. If refresh token expire, go to sign in


That's all. Check it.