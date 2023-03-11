<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    private CategoryRepositoryInterface $categoryRepository;

    public function __construct(CategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function add(CategoryRequest $request)
    {
        $this->categoryRepository->addCategory($request);

        return response()->json('Category added');
    }

    public function getCategoryList()
    {
        return response()->json($this->categoryRepository->getCategoryList());
    }

    public function getCategories()
    {
        return response()->json($this->categoryRepository->getCategories());
    }

    public function deleteCategory(Request $request)
    {
        $this->categoryRepository->deleteCategory($request);

        return response()->json('Category deleted');
    }
}
