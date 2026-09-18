<?php

namespace App\Http\Traits;

trait ApiStructure
{
    protected function success($data, string $message = 'Success', int $code = 200, array $meta = [])
    {
        $response = [
            'message' => $message,
            'data'    => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    protected function error(string $message, int $code = 400, $errors = null)
    {
        $response = [
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}