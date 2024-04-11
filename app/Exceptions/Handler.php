<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

// exception lists
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use stdClass;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {

        $this->renderable(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'result' => 0,
                    'message' => 'သင့်ကို ယခုလုပ်ဆောင်ချက်အတွက် ခွင့်ပြုထားခြင်းမရှိပါ။.',
                    'data' => new stdClass
                ], 403);
            }
        });
        
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'result' => 0,
                    'message' => 'Record not found.',
                    'data' => new stdClass
                ], 404);
            }
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'result' => 0,
                    'message' => 'Record not found.',
                    'data' => new stdClass
                ], 404);
            }
        });
        

        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'result' => 0,
                    'message' => 'Unauthenticated.',
                    'data' => new stdClass
                ], 401);
            }
        });
        
        $this->renderable(function (AuthorizationException  $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'result' => 0,
                    'message' => 'Unauthorized.',
                    'data' => new stdClass
                ], 403);
            }
        });

        
    }
}
