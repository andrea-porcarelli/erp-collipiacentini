<?php

namespace App\Http\Controllers;

use App\Facades\Utils;
use App\Traits\Translatable;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;

abstract class Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, Translatable;

    public function exception(Exception $e, $request = null) : JsonResponse|View {
        Log::info($request->expectsJson() . ' : ' . ((Auth::check() ? '[User ID: ' . Auth::id() . '] ' : '[Session ID: ' . Session::getId() . '] ') . ': ' . $e->getMessage()) . ' in ' . $e->getFile() . ' at line ' . $e->getLine());
        if (!is_null($request) && $request->expectsJson()) {
            return response()->json([
                'message' => $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine(),
                'errors' => isset($e->validator) ? $e->validator->getMessageBag() : '',
                'trace' => $e->getTraceAsString()
            ], 422);
        }
        dd($e);
    }

    public function success(array $response = ['response' => 'success']) : JsonResponse {
        return response()->json($response);
    }

    public function error(array $response) : JsonResponse {
        return response()->json($response, 422);
    }

    public function folder_path($method) : string
    {
        $class_name = substr(str_replace(__NAMESPACE__, '', get_class($this)), 1);
        $explode = explode("\\", $class_name);
        $folder = Str::kebab($explode[0]);
        $sub_folder = str_replace('-controller', '', Str::kebab($explode[1]));

        return $folder . '.' . Str::plural($sub_folder) . '.' . $this->method($method);
    }

    public function method($method) : string
    {
        return explode('::', $method)[1];
    }

    public function route_name($controller) : string
    {
        return Str::plural(Str::snake(str_replace('Controller', '', class_basename($controller))));
    }

    public function editColumns($datatableModel, $model, array $options = [], $modal = null) {
        return $datatableModel->addColumn('action', function ($item) use($model, $options, $modal) {
            return view('backoffice.components.datatable', [
                'item' => $item,
                'model' => $model,
                'options' => $options,
                'modal' => $modal ?? '',
            ]);
        });
    }

    public function _edit($interface, $id, $request, array $custom_fields = []) : JsonResponse|Model
    {
        try {
            $element = $interface->find($id);
            if ($element->id) {
                $request->merge(self::convert_date($request));
                $parameters = $request->all();
                $execute = DB::transaction(function () use($element, $parameters, $interface, $custom_fields) {
                    if ($interface->edit($element, $parameters)) {
                        if (isset($parameters['languages'])) {
                            $languages = $this->store_languages($element, $parameters['languages'], $custom_fields);
                            if (isset($languages['error'])) {
                                throw new Exception($languages['error']);
                            }
                        }
                    }
                    return true;
                });
                if ($execute) {
                    return $element;
                }
                throw new Exception('Error update: ' . $interface->getModel() . ' ' . $id);
            }
            throw new Exception('Element not found');
        } catch (Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function _store($interface, $request, array $custom_fields = [], bool $slug = false)
    {
        $request->merge(self::convert_date($request));
        $parameters = $request->all();

        return DB::transaction(function () use($parameters, $interface, $custom_fields, $slug) {
            $element = $interface->store($parameters);
            if (isset($parameters['languages'])) {
                $languages = $this->store_languages($element, $parameters['languages'], $custom_fields, $slug);
                if  (isset($languages['error'])) {
                    throw new Exception($languages['error']);
                }
            }
            return $element;
        });
    }

    private function convert_date($request) : array {
        $dates = ['from', 'to', 'stay_from', 'stay_to'];
        $parameters = [];
        foreach ($dates as $date) {
            if ($request->has($date)) {
                $parameters[$date] = Utils::data_from_ita($request->{$date});
            }
        }
        return $parameters;
    }

    public function search(Request $request) : JsonResponse {
        $parameters = $request->all();
        $fields = ['surname', 'email', 'phone'];
        $field = $parameters['field'];
        $value = $parameters['value'];
        $elements = $this->interface->builder()
            ->when(is_array($field), function ($q) use($field, $fields, $value) {
                $q->where(function($q) use ($field, $fields, $value) {
                    foreach ($field as $key) {
                        if (in_array($key, $fields)) {
                            $q->orWhere($key, 'like', '%' . $value . '%');
                        }
                    }
                });
            })
            ->when(!is_array($field), function ($q) use($field, $value) {
                $q->where($field, 'like', '%' . $value . '%');
            });
        $elements = $elements->limit(15)->get()->map(function ($item) {
            return ['id' => $item->id, 'text' => '(' . $item->id . ') ' . $item->search_label];
        })->toArray();
        return response()->json(['results' => $elements, 'pagination' => ['more' => false]]);
    }
}
