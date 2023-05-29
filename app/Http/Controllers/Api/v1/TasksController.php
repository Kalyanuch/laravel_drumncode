<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TasksController extends Controller
{
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
    public function index()
    {
        return response()->json(Task::where('user_id', $this->user_id)->get());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
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

    protected function getTask($id)
    {
        $task = Task::where('id', $id)->where('user_id', $this->user_id)->first();

        if (!$task || $task->status == Task::STATUS_DONE) {
            return ['error' => 'Access denied.', 'status' => false];
        }

        return ['status' => true, 'task' => $task];
    }

}
