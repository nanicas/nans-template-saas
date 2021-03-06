<?php

namespace App\Services;

use App\Services\AbstractService;
use App\Repositories\ConfigUserRepository;
use App\Helpers\Helper;
use App\Validators\ConfigUserValidator;
use App\Handlers\ConfigUserHandler;
use App\Exceptions\CrudException;

class ConfigUserService extends AbstractService
{
    public function __construct(
        ConfigUserRepository $repository,
        ConfigUserValidator $validator,
        ConfigUserHandler $handler
    )
    {
        parent::setRepository($repository);
        parent::setValidator($validator);
        parent::setHandler($handler);
    }

    public function getDataToShow(int $userId)
    {
        if (!Helper::isMaster() && $userId != Helper::getUserId()) {
            throw new CrudException('Você não possui privilégios para visualizar as informações do registro selecionado.');
        }
        
        $row = $this->getConfig($userId);

        $isMaster = Helper::isMaster();

        return compact('row', 'isMaster');
    }

    public function store(array $data)
    {
        $storeData = [
            'name' => $data['name'],
            'admin' => $data['admin'],
            'slug' => Helper::getSlug(),
            'user_id' => Helper::getUserId(),
        ];

        if (!Helper::isMaster() && $storeData['admin'] != 0) {
            $storeData['admin'] = 0;
        }
        
        return $this->getRepository()->store($storeData);
    }

    public function update(array $data)
    {
        $config = $this->getRepository()->getById($data['id']);

        if (empty($config)) {
            throw new CrudException('Não foi possível encontrar uma configuração válida para edição');
        }

        $isSlugFromLoggedUser = ($config->getSlug() == Helper::getSlug());
        $isUserFromLoggedUser = ($config->getUser() == Helper::getUserId());

        if (!$isSlugFromLoggedUser) {
            throw new CrudException(
                    sprintf('A configuração encontrada não pertence ao escopo do usuário logado. user_slug:[%s], row_slug:[%s]',
                        Helper::getSlug(), $config->getSlug())
            );
        }

        if (!Helper::isMaster() && !$isUserFromLoggedUser) {
            throw new CrudException(
                    sprintf('A configuração encontrada não pertence ao usuário e não pode processada. user_id[%s], row_user_id:[%s]',
                        Helper::getUserId(), $config->getUser())
            );
        }

        $updateData = [
            'name' => $data['name']
        ];
        if (Helper::isMaster()) {
            $updateData['admin'] = $data['admin'];
        }

        $config->fill($updateData);

        return $this->getRepository()->update($config);
    }

    private function getConfig(int $userId)
    {
        return $this->getRepository()->getByUserAndSlug(
            $userId, Helper::getSlug()
        );
    }
}