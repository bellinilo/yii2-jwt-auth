<?php
/**
 * Created by PhpStorm.
 * User: mikhail
 * Date: 09.11.2018
 * Time: 11:53
 */

namespace Kakadu\Yii2JwtAuth;

use yii\base\Action;
use yii\di\Instance;
use yii\web\Request;
use yii\web\Response;

/**
 * Class    RefreshTokensAction
 * @package Kakadu\Yii2JwtAuth
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class RefreshTokensAction extends Action
{
    /**
     * @var ApiTokenService|string token component name or instance
     */
    public $apiTokens = 'apiTokens';

    /**
     * @var Request|string response component name or instance
     */
    public $response = 'response';

    /**
     * @var string request component name or instance
     */
    public $request = 'request';

    /**
     * @var string the HTTP refresh token header name
     */
    public $headerRefresh = 'Authorization-Refresh';

    /**
     * @var string the HTTP access token header name
     */
    public $accessToken = 'Authorization';

    /**
     * @inheritdoc
     */
    public $pattern = '/^Jwt\s+(.*?)$/';

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        $this->apiTokens = Instance::ensure($this->apiTokens, ApiTokenService::class);
        $this->response  = Instance::ensure($this->response, Response::class);
        $this->response  = Instance::ensure($this->request, Request::class);
    }

    /**
     * Renew tokens
     *
     * @return void
     */
    public function run(): void
    {
        list('accessToken' => $accessToken, 'refreshToken' => $refreshToken) = $this->getTokens();

        // Convert to jwt token model
        $jwtAccessToken  = $this->apiTokens->getJwtToken($accessToken);
        $jwtRefreshToken = $this->apiTokens->getJwtToken($refreshToken);

        // Renew
        $newTokens = $this->apiTokens->renewJwtToken($jwtAccessToken, $jwtRefreshToken);

        JwtBearerAuth::addJwtToHeader($this->response, $newTokens);
    }

    /**
     * Get tokens from headers
     *
     * @return array
     */
    protected function getTokens(): array
    {
        return [
            'accessToken'  => $this->getAuthHeader($this->request->headers->get($this->accessToken)),
            'refreshToken' => $this->request->headers->get($this->headerRefresh),
        ];
    }

    /**
     * Get jwt token string
     *
     * @param string $authHeader
     *
     * @return null|string
     */
    protected function getJwtTokenString(string $authHeader): ?string
    {
        if ($authHeader !== null && $this->pattern !== null) {
            if (preg_match($this->pattern, $authHeader, $matches)) {
                $authHeader = $matches[1];
            } else {
                return null;
            }
        }

        return $authHeader;
    }
}