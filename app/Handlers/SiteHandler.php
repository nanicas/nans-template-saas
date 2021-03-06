<?php

namespace App\Handlers;

use App\Handlers\AbstractHandler;
use App\Hydrators\ContractHydrator;
use App\Hydrators\SlugHydrator;

class SiteHandler extends AbstractHandler
{
    public function afterGetIndexData()
    {
        $data = & $this->data;

        $emptyContract = (empty($data['contract']));
        $emptySlug = (empty($data['slug']));

        if (!$emptyContract) {
            $data['contract'] = ContractHydrator::staticHydrate($data['contract']);
        }
        if (!$emptySlug) {
            $data['slug'] = SlugHydrator::staticHydrate($data['slug']);
        }
        if ($emptySlug) {
            $data['page'] = 'default';
            return;
        }
        if ($emptyContract || !$data['contract']->isActive()) {
            $data['page'] = 'no_contract';
            return;
        }

        $data['page'] = 'contracted';
    }
}