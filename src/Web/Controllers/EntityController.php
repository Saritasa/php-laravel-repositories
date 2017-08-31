<?php

namespace Saritasa\Web\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Saritasa\Repositories\Base\IRepository;

/**
 * EntityController implements stored entity CRUD operations
 */
abstract class EntityController extends Controller implements IWebResourceController
{
    /* @var IRepository */
    protected $repo;

    protected function __construct(IRepository $repository)
    {
        $this->repo = $repository;
    }

    public function index(Request $request): Response
    {
        // TODO: Implement index() method.
    }
}
