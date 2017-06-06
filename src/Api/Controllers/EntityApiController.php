<?php

namespace Saritasa\Api\Controllers;

use App\Models\Support\PagingType;
use Dingo\Api\Http\Request;
use Dingo\Api\Http\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Saritasa\Transformers\IDataTransformer;
use Saritasa\DingoApi\Traits\PaginatedOutput;

abstract class EntityApiController extends BaseApiController implements IApiResourceController
{
    const ID_REQUIRED = ['id' => 'required|integer|min:1'];
    use PaginatedOutput;

    /**
     * Repository, responsible for entities storage
     * @var IRepository
     */
    protected $repo;

    /**
     * Class name of entity, managed by this controller
     * @var string
     */
    protected $modelClass;

    /**
     * If lists output should be paginated by default
     * @var PagingType
     */
    private $paging = PagingType::PAGINATOR;

    public function __construct(string $modelClass, IDataTransformer $transformer = null)
    {
        parent::__construct($transformer);
        $this->modelClass = $modelClass;
    }

    public function index(Request $request): Response
    {
        $this->validateIndexRequest($request);
        $searchValues = $request->only($this->repo->searchableFields);

        switch ($this->paging) {
            case PagingType::PAGINATOR:
                $result = $this->repo->getPage($this->readPaging($request), $searchValues);
                break;
            case PagingType::CURSOR:
                $result = $this->repo->getCursorPage($this->readCursor($request), $searchValues);
                break;
            default:
                $result = $this->repo->getWhere($searchValues);

        }
        return $this->json($result);
    }

    /**
     * Verify, that:
     * - user does not request search by unsupported fields
     *
     * @throws ValidationException
     * @param Request $request
     */
    protected function validateIndexRequest(Request $request)
    {
        $query = $request->get('q');
        if ($query && is_string($query)) {
            if (in_array($query, $this->repo->searchableFields)) {
                // TODO: implement
                throw new ValidationException("Search by field $query is not supported");
            }
        }
    }

    public function create(Request $request): Response
    {
        $this->validateCreateRequest($request);

        /* @var Model $model */
        $model = new $this->modelClass($request->all());
        $model = $this->repo->create($model);
        return $this->json($model);
    }

    /**
     * @throws ValidationException
     * @param Request $request
     */
    protected function validateCreateRequest(Request $request)
    {
        $rules = $this->repo->getModelValidationRules();
        $this->validate($request, $rules);
    }

    /**
     * @throws ValidationException
     * @param Request $request
     * @param Model $model
     */
    protected function validateShowRequest(Request $request, Model $model)
    {
    }

    public function show(Request $request, string $id): Response
    {
        $model = $this->repo->findOrFail($id);
        $this->validateShowRequest($request, $model);
        return $this->json($model);
    }

    /**
     * @throws ValidationException
     * @param Request $request
     * @param Model $model
     * @return Model
     */
    protected function validateUpdateRequest(Request $request, Model $model)
    {
        /* @var Model $model */
        $model->fill($request->all());
        $rules = $this->repo->getModelValidationRules($model);
        $this->validate($request, $rules);
        return $model;
    }

    public function update(Request $request, string $id): Response
    {
        $model = $this->repo->findOrFail($id);

        $this->validateUpdateRequest($request, $model);

        $model->fill($request->all());
        $model = $this->repo->save($model);
        return $this->json($model);
    }

    /**
     * @throws ValidationException
     * @param Request $request
     * @param Model $model
     */
    protected function validateDestroyRequest(Request $request, Model $model)
    {
    }

    public function destroy(Request $request, string $id): Response
    {
        $model = $this->repo->findOrFail($id);
        $this->validateDestroyRequest($request, $model);

        $this->repo->delete($model);
        return $this->response->noContent();
    }
}
