<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Facades\Utils;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Backoffice\Requests\StoreCategoryRequest;
use App\Interfaces\CategoryInterface;
use App\Interfaces\OrderInterface;
use App\Interfaces\ProductInterface;
use App\Models\Category;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends CrudController
{
    use AuthorizesRequests, ValidatesRequests;

    public CategoryInterface $interface;
    public string $path;

    public function __construct(CategoryInterface $interface)
    {
        $this->interface = $interface;
        $this->path = 'categories';
    }

    public function index(): View
    {
        $statuses = OrderStatus::statuses();
        return view('backoffice.' . $this->path . '.index', compact('statuses'));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = [
            'label'         => $request->get('label'),
            'category_code' => $request->get('category_code'),
            'iva'           => $request->get('iva'),
        ];

        $category = $this->interface->store($data);

        return $this->success(['redirect' => route($this->path . '.show', $category->id)]);
    }

    public function update(StoreCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $category = $this->interface->find($id);
            $this->interface->edit($category, [
                'label'         => $request->get('label'),
                'category_code' => $request->get('category_code'),
                'iva'           => $request->get('iva'),
            ]);
            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function data(Request $request) : JsonResponse {
        try {
            $filters = $request->get('filters') ?? [];

            $elements = $this->interface->filters($filters);
            return $this->editColumns(datatables()->of($elements), $this->route_name(__CLASS__), ['edit', 'status'])
                ->addColumn('created_at', function ($item) {
                    return Utils::data_long($item->created_at);
                })
                ->addColumn('category', function ($item) {
                    return $item->label;
                })
                ->addColumn('products', function ($item) {
                    return $item->products()->count();
                })
                ->addColumn('options', function ($item) {
                    return ' > ';
                })
                ->rawColumns(['status'])
                ->toJson();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }
}
