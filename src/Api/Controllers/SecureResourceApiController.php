<?php

namespace Saritasa\Api\Controllers;

use App\Api\Transformers\BaseTransformer;
use Dingo\Api\Http\Request;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Saritasa\Repositories\Base\IRepository;

/**
 * Verify, if user can has permission to perform requested operation
 *
 * You must implement and register corresponding policy for selected model
 */
class SecureResourceApiController extends EntityApiController
{
    use AuthorizesRequests;

    public function __construct(Gate $gate, IRepository $repository, BaseTransformer $transformer = null)
    {
        parent::__construct($repository->getModelClass(), $transformer);
        $this->repo = $repository;
        $gate->getPolicyFor($this->modelClass);
    }

    protected function validateCreateRequest(Request $request)
    {
        parent::validateCreateRequest($request);
        $this->authorize('create', $this->modelClass);
    }

    protected function validateUpdateRequest(Request $request, Model $model)
    {
        parent::validateUpdateRequest($request, $model);
        $this->authorize('update', $model);
    }

    protected function validateShowRequest(Request $request, Model $model)
    {
        parent::validateShowRequest($request, $model);
        $this->authorize('view', $model);
    }

    protected function validateIndexRequest(Request $request)
    {
        parent::validateIndexRequest($request);
        $this->authorize('enumerate', $this->modelClass);
    }

    protected function validateDestroyRequest(Request $request, Model $model)
    {
        $model = parent::validateDestroyRequest($request, $model);
        $this->authorize('delete', $model);
    }
}
