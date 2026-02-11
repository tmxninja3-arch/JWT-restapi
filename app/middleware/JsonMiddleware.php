<?php
namespace App\Middleware;

use App\Helpers\Response;

class JsonMiddleware {
    public function handle(&$request) {
        // Set JSON response header
        header('Content-Type: application/json');

        // Check if request method requires body
        if (in_array($request['method'], ['POST', 'PUT', 'PATCH'])) {
            $contentType = $request['headers']['Content-Type'] ?? '';

            // Check Content-Type header
            if (strpos($contentType, 'application/json') === false) {
                Response::error('Content-Type must be application/json', 400);
                return false;
            }

            // Read raw input
            $rawInput = file_get_contents('php://input');

            if (empty($rawInput)) {
                Response::error('Request body cannot be empty', 400);
                return false;
            }

            // Decode JSON
            $decodedBody = json_decode($rawInput, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Invalid JSON payload', 400);
                return false;
            }

            $request['body'] = $decodedBody;
        }

        return $request;
    }
}