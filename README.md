### Step-by-Step Guide for Laravel Project

### Step 1: Default Breeze Setup

1. **Create a New Laravel Project**
   ```sh
   laravel new laravel-todo
   ```

2. **Choose a Starter Kit**
   When prompted, select **Laravel Breeze**:
   ```sh
   Would you like to install a starter kit? [No starter kit]:
   [breeze   ] Laravel Breeze
   ```

3. **Choose the Breeze Stack**
   Select the **API only** stack:
   ```sh
   Which Breeze stack would you like to install? [Blade with Alpine]:
   [api                ] API only
   ```

4. **Select a Testing Framework**
   Choose **Pest**:
   ```sh
   Which testing framework do you prefer? [Pest]:
   [0] Pest
   ```

5. **Choose a Database**
   Select **SQLite**:
   ```sh
   Which database will your application use? [SQLite]:
   [sqlite ] SQLite
   ```

6. **Run Database Migrations**
   Confirm to run the migrations:
   ```sh
   Would you like to run the default database migrations? (yes/no) [yes]: yes
   ```

7. **Install Dependencies and Build Frontend**
   Navigate to your project directory and run:
   ```sh
   cd laravel-todo
   php artisan serve
   ```

### Step 2: User Setup (CRUD by Admin)

#### 1. **Migration: Add Role to Users Table**
Add the `role` field to the users table migration:
```sh
php artisan make:migration add_role_to_users_table --table=users
```
Update migration:
```php
<?php

public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default('user');
    });
}
```
Run the migration:
```sh
php artisan migrate
```

#### 2. **Model: User**
Ensure the `User` model includes the `HasApiTokens` trait, the `role` attribute, and the relationship with `Todo`:
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    public function todos()
    {
        return $this->hasMany(Todo::class);
    }

    // Other model properties and methods
}
```

#### 3. **Factory: UserFactory**
Ensure the user factory includes the role attribute:
```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // password
            'role' => 'user', // default role
            'remember_token' => Str::random(10),
        ];
    }
}
```

#### 4. **Request: StoreUserRequest and UpdateUserRequest**
Create requests for validation:
```sh
php artisan make:request StoreUserRequest
php artisan make:request UpdateUserRequest
```

**StoreUserRequest**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:user,admin',
        ];
    }
}
```

**UpdateUserRequest**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $this->user->id,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|string|in:user,admin',
        ];
    }
}
```

#### 5. **Resource: UserResource**
Create a resource class:
```sh
php artisan make:resource UserResource
```

**UserResource**
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

#### 6. **Controller: UserController**
Create a controller for managing users:
```sh
php artisan make:controller UserController
```

**UserController**
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return UserResource::collection(User::all());
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->validated());

        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->noContent();
    }
}
```

#### 7. **Create Middleware**

Create a middleware to handle role checking:
```sh
php artisan make:middleware RoleMiddleware
```

#### 8. **Define RoleMiddleware**
Update the generated middleware:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check() || !in_array(Auth::user()->role, $roles)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        return $next($request);
    }
}
```

#### 9. **Register Middleware in bootstrap/app.php**

Open the `bootstrap/app.php` file and register the middleware:
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

#### 10. **Update Routes in api.php**
Use the middleware in your routes in `routes/api.php`:
```php
<?php

use App\Http\Controllers\TodoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::resource('users', UserController::class)->middleware('role:admin');
    Route::resource('todos', TodoController::class)->middleware('role:user,admin');
});
```

#### 11. **Testing: UserTest**
Create a test for CRUD operations on users:
```sh
php artisan make:test UserTest --pest
```

Perfect! Let's continue from where we left off, using `php artisan make:model Todo -a` to generate the model, migration, factory, seeder, policy, controller, and form requests. Then, we'll create the resource separately with `php artisan make:resource TodoResource`.

### Step-by-Step Guide Continued

### Step 2: User Setup (CRUD by Admin)

#### 11. **Testing: UserTest**

**UserTest Continued**
```php
test('admin can retrieve all users', function () {
    Sanctum::actingAs($this->admin, ['*']);

    $response = getJson('/api/users');

    $response->assertStatus(200)
             ->assertJsonCount(User::count(), 'data');
});

test('admin can create a new user', function () {
    Sanctum::actingAs($this->admin, ['*']);

    $newUserData = [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password',
        'role' => 'user',
    ];

    $response = postJson('/api/users', $newUserData);

    $response->assertStatus(201)
             ->assertJson(['data' => ['name' => 'New User']]);
});

test('admin can update an existing user', function () {
    Sanctum::actingAs($this->admin, ['*']);

    $updateUserData = [
        'name' => 'Updated User',
        'email' => 'updated@example.com',
    ];

    $response = putJson("/api/users/{$this->user->id}", $updateUserData);

    $response->assertStatus(200)
             ->assertJson(['data' => ['name' => 'Updated User']]);
});

test('admin can delete a user', function () {
    Sanctum::actingAs($this->admin, ['*']);

    $response = deleteJson("/api/users/{$this->user->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
});
```

### Step 3: Todo Setup

#### 1. **Generate Todo Model with Associated Components**
   Generate the Todo model, migration, factory, seeder, policy, controller, and form requests:
   ```sh
   php artisan make:model Todo -a
   ```

#### 2. **Create Todo Resource**
   Create the Todo resource separately:
   ```sh
   php artisan make:resource TodoResource
   ```

#### 3. **Migration: Create Todos Table**
Define the schema in the generated migration file:
```php
<?php

public function up()
{
    Schema::create('todos', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->timestamps();
    });
}
```
Run the migration:
```sh
php artisan migrate
```

#### 4. **Model: Todo**
Create the Todo model and include the relationship with User:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

#### 5. **Factory: TodoFactory**
Customize the generated factory:
```php
<?php

namespace Database\Factories;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TodoFactory extends Factory
{
    protected $model = Todo::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'user_id' => User::factory(),
        ];
    }
}
```

#### 6. **Seeder: TodoSeeder, DatabaseSeeder**
Create the TodoSeeder logic:
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Todo;

class TodoSeeder extends Seeder
{
    public function run()
    {
        User::factory(5)
            ->hasTodos(10)
            ->create()
    }
}
```

Create the DatabaseSeeder logic:
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([UserSeeder::class, TodoSeeder::class]);
    }
}
```
Run the seeder:
```sh
php artisan db:seed
```

#### 7. **Request: StoreTodoRequest and UpdateTodoRequest**
Implement validation rules in the generated request classes:

**StoreTodoRequest**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTodoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
        ];
    }
}
```

**UpdateTodoRequest**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTodoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
        ];
    }
}
```

#### 8. **Resource: TodoResource**
Customize the generated resource class:
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TodoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

#### 9. **Policy: TodoPolicy**
Define authorization logic:
```php
<?php

namespace App\Policies;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TodoPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Todo $todo)
    {
        return $user->id === $todo->user_id || $user->role === 'admin';
    }

    public function create(User $user)
    {
        return $user->role === 'user' || $user->role === 'admin';
    }

    public function update(User $user, Todo $todo)
    {
        return $user->id === $todo->user_id || $user->role === 'admin';
    }

    public function delete(User $user, Todo $todo)
    {
        return $user->id === $todo->user_id || $user->role === 'admin';
    }
}
```

#### 10. **Controller: TodoController**
Implement CRUD operations in the generated controller:
```php
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
```

#### 11. **Routes: api.php**
Define API routes in `api.php`:
```php
<?php

use App\Http\Controllers\TodoController;
use App\Http\Controllers\UserController;

Route::middleware('auth:sanctum')->group(function () {
    Route::resource('users', UserController::class)->middleware('role:admin');
    Route::resource('todos', TodoController::class)->middleware('role:user,admin');
});
```

### Step 3: Todo Setup

#### 12. **Testing: TodoTest**
Create a test for CRUD operations on todos:
```sh
php artisan make:test TodoTest
```

```php
test('user can retrieve their todos', function () {
    Sanctum::actingAs($this->user, ['*']);
    $todos = Todo::factory(5)->create(['user_id' => $this->user->id]);

    $response = getJson('/api/todos');

    $response->assertStatus(200)
             ->assertJsonCount(5, 'data');
});

test('user can create a todo', function () {
    Sanctum::actingAs($this->user, ['*']);

    $response = postJson('/api/todos', ['title' => 'New Todo']);

    $response->assertStatus(201)
             ->assertJson(['title' => 'New Todo']);
});

test('user can update their own todo', function () {
    Sanctum::actingAs($this->user, ['*']);
    $todo = Todo::factory()->create(['user_id' => $this->user->id]);

    $response = putJson("/api/todos/{$todo->id}", ['title' => 'Updated Todo']);

    $response->assertStatus(200)
             ->assertJson(['title' => 'Updated Todo']);
});

test('admin can update any todo', function () {
    Sanctum::actingAs($this->admin, ['*']);
    $todo = Todo::factory()->create(['user_id' => $this->user->id]);

    $response = putJson("/api/todos/{$todo->id}", ['title' => 'Admin Updated Todo']);

    $response->assertStatus(200)
             ->assertJson(['title' => 'Admin Updated Todo']);
});

test('user cannot delete another user\'s todo', function () {
    Sanctum::actingAs($this->user, ['*']);
    $todo = Todo::factory()->create();

    $response = deleteJson("/api/todos/{$todo->id}");

    $response->assertStatus(403);
});

test('admin can delete any todo', function () {
    Sanctum::actingAs($this->admin, ['*']);
    $todo = Todo::factory()->create();

    $response = deleteJson("/api/todos/{$todo->id}");

    $response->assertStatus(204);
});
```

By following these steps, you will have set up a comprehensive testing suite using Pest for both User and Todo models, with proper role-based access control and CRUD operations.
