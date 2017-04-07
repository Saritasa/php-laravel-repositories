<?php
namespace Saritasa\Repositories;

use App\Models\User;
use Saritasa\Repositories\Base\Repository;

class UserRepository extends Repository
{
    /**
     * FQN model name of the repository.
     * @var string
     */
    protected $modelClass = User::class;

    /**
     * List of fields, allowed to use in the search
     * @var array
     */
    protected $searchableFields = [
        'name',
        'email',
    ];
}
