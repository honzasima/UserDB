<?php

namespace App\V1Module\Presenters;

use Nette\Application\Responses\JsonResponse;
use Nette\Http\Response;
use Nette;

class UserPresenter extends ApiPresenter
{
    private $user;

    function __construct(\App\Model\Uzivatel $user)
    {
        $this->user = $user;
    }

    private function getCurrentUser($uid)
    {
        $user = null;

        if ($uid === null) {
            $user = $this->user->getUzivatel($this->jwt->uid);
            if (!$user) {
                $this->sendErrorResponse(Response::S404_NOT_FOUND, 'App error, userid not found');
            }
        } else {
            $this->verifyAdministrator();
            $user = $this->user->getUzivatel($uid);
            if (!$user) {
                $this->sendErrorResponse(Response::S404_NOT_FOUND, 'App error, userid not found');
            }
        }

        return $user;
    }

    public function renderDefault($uid)
    {

        $this->verifyToken();

        $user = $this->getCurrentUser($uid);

        $this->sendSuccessResponse(
            new JsonResponse($this->toJson($user))
        );
    }

    static function toJson($user)
    {
        return [
            'id' =>  $user->id,
            'firstName' => $user->jmeno,
            'lastName' => $user->prijmeni,
            'membership' => $user->TypClenstvi->text,
            'email' => $user->email,
            'phone' => $user->telefon,
            'ap' => [
                "id" => $user->Ap_id,
                "name" => $user->Ap->jmeno,
            ],
            'area' => [
                "id" => $user->Ap->Oblast_id,
                "name" => $user->Ap->Oblast->jmeno,
            ]
        ];
    }
}
