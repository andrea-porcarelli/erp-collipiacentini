<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CrudController extends Controller
{
    public function status(int $id): JsonResponse
    {
        try {
            $model = $this->interface->find($id);
            $this->interface->edit($model, ["is_active" => !$model->is_active]);
            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e, null);
        }
    }

    public function show(int $id)
    {
        $model = $this->interface->find($id);
        return view('backoffice.' . $this->path . '.show', compact('model'))
            ->with('path', $this->path);
    }
}
