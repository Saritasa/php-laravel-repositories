<?php

namespace Saritasa\Web\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

interface IWebResourceController
{
    public function index(Request $request): Response;
    public function create(Request $request): Response;
    public function store(Request $request): Response;
    public function show(Request $request): Response;
    public function edit(Request $request): Response;
    public function update(Request $request): Response;
    public function destroy(Request $request): Response;
}