<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTodoRequest;
use App\Http\Requests\UpdateTodoRequest;
use App\Http\Resources\TodoResource;
use App\Models\Todo;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class TodoController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        return TodoResource::collection(Todo::latest()->get());
    }

    public function show(Todo $todo)
    {
        $this->authorize('view', $todo);

        return new TodoResource($todo);
    }

    public function store(StoreTodoRequest $request)
    {
        $todo = Todo::create($request->validated() + ['user_id' => Auth::id()]);

        return new TodoResource($todo);
    }

    public function edit(Todo $todo)
    {
        $this->authorize('update', $todo);

        return new TodoResource($todo);
    }

    public function update(UpdateTodoRequest $request, Todo $todo)
    {
        $this->authorize('update', $todo);

        $todo->update($request->validated());

        return new TodoResource($todo);
    }

    public function destroy(Todo $todo)
    {
        $this->authorize('delete', $todo);

        $todo->delete();

        return response()->noContent();
    }
}
