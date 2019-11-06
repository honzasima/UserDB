<?php

namespace App\V1Module\Presenters;

use Nette\Application\Responses\JsonResponse;
use Nette\Http\Response;
use Nette;
use \Firebase\JWT\JWT;

class AuthPresenter extends ApiPresenter
{
    private $user;
    private $honoraryMembership;

    function __construct(\App\Model\Uzivatel $user, \App\Model\CestneClenstviUzivatele $honoraryMembership)
    {
        $this->user = $user;
        $this->honoraryMembership = $honoraryMembership;
    }

    public function renderLogin()
    {
        if ($this->request->method != 'POST') {
            $this->sendLoginFailed('Bad method only POST');
        }

        if ($this->getFailedLoginAttempts() > 5) {
            $this->sendErrorResponse(Response::S429_TOO_MANY_REQUESTS, 'Too many unsuccessful attempts, try again in 15 minutes');
        }

        $uid = $this->request->getPost('uid');
        $password = $this->request->getPost('password');

        $u = $this->user->getUzivatel($uid);
        if (!$u) {
            $this->sendLoginFailed($uid, "User not exist");
        }

        if ($u->TypClenstvi_id <= 1) {
            $this->sendLoginFailed($uid, 'You have not in organization');
        }


        if ($u->heslo_strong_hash === $this->user->generateStrongHash($password)) {
            $playload = (array) $this->jwt;
            $playload["uid"] = $uid;
            $playload["administrator"] = $this->isAdministrator();

            $jwtToken = $this->jwtEncode($playload);
            $token = $this->token->insertAplikaceToken($uid, $jwtToken);
            $this->log->log('api.mobile.auth.login.successful', array($token->id));
            $this->sendSuccessResponse(
                new JsonResponse(
                    [
                        'token' => $token->token
                    ]
                )
            );
        }

        $this->sendLoginFailed($uid, 'Bad password');
    }

    private function getFailedLoginAttempts()
    {
        return ($this->log->getLogy()
            ->where('action', 'api.mobile.auth.login.failed')
            ->where('ip', $this->httpRequest->remoteAddress)
            ->where('time > ?', new Nette\Utils\DateTime('now - 15 minutes'))
            ->count());
    }

    private function sendLoginFailed($uid = '', string $message)
    {
        $this->log->log('api.mobile.login.failed', array($uid));
        $this->sendErrorResponse(Response::S401_UNAUTHORIZED, $message);
    }


    private function isAdministrator()
    {
        $honoraryMembership = $this->honoraryMembership;
        return $honoraryMembership->getHasCC($this->jwt->uid) ? $honoraryMembership->getCC($this->jwt->uid)->TypCestnehoClenstvi_id >= 3 &&  $honoraryMembership->getCC($this->jwt->uid)->TypCestnehoClenstvi_id <= 4 : false;
    }
}
