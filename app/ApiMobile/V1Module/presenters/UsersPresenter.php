<?php

namespace App\V1Module\Presenters;

use Nette\Application\Responses\JsonResponse;
use Nette\Http\Response;
use Nette;

class UsersPresenter extends ApiPresenter
{
    private $user;

    function __construct(\App\Model\Uzivatel $user)
    {
        $this->user = $user;
    }

    public function renderDefault($uid)
    {

        $this->verifyToken();
        $this->verifyAdministrator();

        $users = $this->user->getSeznamUzivatelu();

        $listUsers = array();

        foreach($users as $u){
           array_push($listUsers, UserPresenter::toJson($u));
        }

        $this->sendSuccessResponse(
            new JsonResponse(
                $listUsers
            )
        );
    }



}
