<?php

namespace Package\Pivel\Hydro2\Models\HTTP;

use Package\Pivel\Hydro2\Core\Models\HTTP\StatusCode;

class JsonResponse extends Response
{
    public function __construct(?array $data=null, StatusCode $status=StatusCode::OK, ?string $error_message=null, ?int $error_code=null, array $headers=[])
    {
        $status_type = 'error';
        if ($status->value >= 200 && $status->value < 300) {
            $status_type = 'success';
        } elseif ($status->value >= 400 && $status->value < 500) {
            $status_type = 'fail';
        }
        $json = [
            'status' =>  $status_type,
            'status_code' => $status->value,
            'data' => $data,
            'message' => $error_message,
            'code' => $error_code,
        ];
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        parent::__construct(json_encode($json), true, $status, $headers);
    }
}