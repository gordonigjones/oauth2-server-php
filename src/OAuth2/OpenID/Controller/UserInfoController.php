<?php

namespace OAuth2\OpenID\Controller;

use OAuth2\Scope;
use OAuth2\TokenType\TokenTypeInterface;
use OAuth2\Storage\AccessTokenInterface;
use OAuth2\OpenID\Storage\UserClaimsInterface;
use OAuth2\Controller\ResourceController;
use OAuth2\ScopeInterface;
use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;
use CL_ClientPC;

/**
 * @see OAuth2\Controller\UserInfoControllerInterface
 */
class UserInfoController extends ResourceController implements UserInfoControllerInterface
{
    /**
     * @var UserClaimsInterface
     */
    protected $userClaimsStorage;

    /**
     * Constructor
     *
     * @param TokenTypeInterface   $tokenType
     * @param AccessTokenInterface $tokenStorage
     * @param UserClaimsInterface  $userClaimsStorage
     * @param array                $config
     * @param ScopeInterface       $scopeUtil
     */
    public function __construct(TokenTypeInterface $tokenType, AccessTokenInterface $tokenStorage, UserClaimsInterface $userClaimsStorage, $config = array(), ScopeInterface $scopeUtil = null)
    {
        parent::__construct($tokenType, $tokenStorage, $config, $scopeUtil);

        $this->userClaimsStorage = $userClaimsStorage;
    }

    /**
     * Handle the user info request
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    public function handleUserInfoRequest(RequestInterface $request, ResponseInterface $response)
    {
        if (!$this->verifyResourceRequest($request, $response, 'openid')) {
            return;
        }

        include_once('sites/all/modules/core_framework/includes/CL_ClientPC.inc');

        $token = $this->getToken();
        $claims = $this->userClaimsStorage->getUserClaims($token['user_id'], $token['scope']);
        // The sub Claim MUST always be returned in the UserInfo Response.
        // http://openid.net/specs/openid-connect-core-1_0.html#UserInfoResponse
        $claims += array(
            'sub' => $token['user_id'],
        );

        // WealthPlan, add drupal user roles to UserInfo request
        $account = user_load($token['user_id']);
        $claims['userRoles'] = $account->roles;
        $claims['name'] = $account->name;
        $claims['csub'] = CL_ClientPC::clarityIDFromDrupalAccount($token['user_id']);
        // WealthPlan

        $response->addParameters($claims);
    }
}
