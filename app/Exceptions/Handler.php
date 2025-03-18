<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Arr;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        $data = [
            'result' => 'error',
            'message' => $exception->getMessage() ?? 'Error interno del servidor',
        ];

        switch (class_basename($exception)) {
            case 'RouteNotFoundException':
                $code = 404;
                break;
            case 'ModelNotFoundException':
                $code = 404;
                $data['message'] = \App::make($exception->getModel())->modelNotFoundMessage ?? 'Elemento no encontrado';
                break;
            case 'NotFoundHttpException':
                $code = 404;
                $message = $exception->getMessage() != '' ? $exception->getMessage() : null;
                $data['message'] = $message ?? 'Elemento no encontrado';
                break;
            case 'ValidationException':
                $code = $exception->status;
                if ($exception->errors()) {
                    $data['errors'] = Arr::collapse($exception->errors());
                }
                break;
            case 'HttpException':
                $code = $exception->getStatusCode();
                if (array_key_exists('errors', $exception->getHeaders())) {
                    $data['errors'] = $exception->getHeaders()['errors'];
                }
                break;
        }

        return response()->json($data, $code ?? 500);

        //return parent::render($request, $exception);
    }
}
