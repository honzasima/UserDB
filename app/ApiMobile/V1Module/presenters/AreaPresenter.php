<?php

namespace App\V1Module\Presenters;

use Nette\Application\Responses\JsonResponse;
use Rx\Observable;
use React\EventLoop\Factory;
use Rx\Scheduler;


class AreaPresenter extends ApiPresenter
{
    private $area;

    function __construct(\App\Model\Oblast $area)
    {
        $this->area = $area;
    }

    public function renderDefault()
    {
        $data = [];
        $areas = $this->area->getSeznamOblasti();
        $i = 0;
        foreach ($areas as $area) {
            $data[$i] = [
                'id' => $area['id'],
                'jmeno' => $area['jmeno'],
            ];
            // associated APs
            $aps = $area->related('Ap.Oblast_id')->order("jmeno");
            $apData = [];
            $ii = 0;
            foreach ($aps as $ap) {
                $apData[$ii] = [
                    'id' => $ap['id'],
                    'jmeno' => $ap['jmeno'],
                ];
                $ii++;
            }
            $data[$i]['ap'] = $apData;
            // associated admins
            $spravci = [];
            $ii = 0;
            foreach ($area->related('SpravceOblasti.Oblast_id')->where('SpravceOblasti.od < NOW() AND (SpravceOblasti.do IS NULL OR SpravceOblasti.do > NOW())') as $spravceMtm) {
                $spravce = $spravceMtm->ref('Uzivatel', 'Uzivatel_id');
                $role = $spravceMtm->ref('TypSpravceOblasti', 'TypSpravceOblasti_id');
                $spravci[$ii] = [
                    'id' => $spravce['id'],
                    'nick' => $spravce['nick'],
                    'email' => $spravce['email'],
                    'role' => $role['text'],
                ];
                $ii++;
            }
            $data[$i]['admins'] = $spravci;
            $i++;
        }
        $this->sendSuccessResponse( new JsonResponse($data) );
    }
}
