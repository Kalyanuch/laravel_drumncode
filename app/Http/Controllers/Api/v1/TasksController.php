<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * API tasks controller.
 */
class TasksController extends Controller
{
    /**
     * Current uer ID.
     *
     * @var int|String
     */
    protected $user_id;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->user_id = Auth::guard('api')->user()->id;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Task::where('user_id', $this->user_id);

        if ($request->has('filter')) {
            $this->buildFilters($query, $request->get('filter'));
        }

        if ($request->has('sort_by') && in_array($request->get('sort_by'), $this->getSortKeys())) {
            $order_by = ($request->has('order_by') && in_array($request->get('order_by'), ['asc', 'desc'])) ? $request->get('order_by') : 'asc';

            $query->orderBy($request->get('sort_by'), $order_by);
        }

        return response()->json($query->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required|max:255',
            'priority' => 'required|between:1,5',
            'finish_at' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data = $request->except(['api_token']);

        if ($data['parent_id']) {
            $parent = Task::find($data['parent_id']);

            if (!$parent || $parent['user_id'] != $this->user_id) {
                return response()->json(['message' => 'Wrong parent_id value.'], 400);
            }
        }

        $data['status'] = $data['status'] ?? Task::STATUS_TODO;
        $data['user_id'] = $this->user_id;
        $data['parent_id'] = $data['parent_id'] ?? 0;

        $task = Task::create($data);

        return response()->json($task, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required|max:255',
            'priority' => 'required|between:1,5',
            'status' => 'required',
            'parent_id' => 'required',
            'finish_at' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data = $request->except(['api_token']);

        $result = $this->getTask($id);

        if (!$result['status']) {
            return response()->json(['message' => $result['error']], 400);
        }

        if ($data['parent_id']) {
            $parent = Task::find($data['parent_id']);

            if (!$parent || $parent['user_id'] != $this->user_id) {
                return response()->json(['message' => 'Wrong parent_id value.'], 400);
            }
        }

        if ($result['task']->status != $data['status'] && count($result['task']->getChildActiveList())) {
            return response()->json(['message' => 'You have child open tasks.'], 400);
        }

        $result['task']->fill($data)->save();

        return response()->json($result['task'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $result = $this->getTask($id);

        if (!$result['status']) {
            return response()->json(['message' => $result['error']], 400);
        }

        $result['task']->delete();

        return response()->json(['message' => 'Removed.'], 200);
    }

    /**
     * Gets the task by ID for current usr.
     *
     * @param $id
     *   Task ID.
     *
     * @return array
     *   Reponse array.
     */
    protected function getTask($id)
    {
        $task = Task::where('id', $id)->where('user_id', $this->user_id)->first();

        if (!$task || $task->status == Task::STATUS_DONE) {
            return ['error' => 'Access denied.', 'status' => false];
        }

        return ['status' => true, 'task' => $task];
    }

    /**
     * Gets available keys for sorting.
     *
     * @return string[]
     *   Array of keys.
     */
    protected function getSortKeys()
    {
        return [
            'priority',
            'created_at',
            'finish_at',
        ];
    }

    /**
     * Builds query according to reponse filters.
     *
     * @param $query
     *   DB query.
     * @param $filters
     *   Response filters.
     */
    protected function buildFilters(&$query, $filters = '')
    {
        $ar_filters = explode(',', $filters);

        foreach ($ar_filters as $filter) {
            $ar_filter_data = explode(':', $filter);

            switch ($ar_filter_data[0]) {
                case 'priority':
                    if (substr_count($ar_filter_data[1], '-')) {
                        $ar_priority = explode('-', $ar_filter_data[1]);

                        $query->where('priority', '>=', $ar_priority[0])->where('priority', '<=', $ar_priority[1]);
                    }
                    else {
                        $query->where('priority', $ar_filter_data[1]);
                    }
                    break;
                case 'status':
                    $query->where('status', $ar_filter_data[1]);
                    break;
                case 'title':
                    $query->where('title', 'like', $ar_filter_data[1] . '%');
                    break;
            }
        }
    }

}
