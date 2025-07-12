<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SwaggerController extends Controller
{
    /**
     * Display the Swagger UI interface
     */
    public function index()
    {
        $swaggerHtml = $this->getSwaggerHtml();

        return response($swaggerHtml, 200, [
            'Content-Type' => 'text/html; charset=utf-8'
        ]);
    }

    /**
     * Serve the OpenAPI specification
     */
    public function spec()
    {
        $yamlPath = base_path('swagger.yaml');

        if (!file_exists($yamlPath)) {
            return response()->json(['error' => 'Swagger specification not found'], 404);
        }

        $yamlContent = file_get_contents($yamlPath);

        return response($yamlContent, 200, [
            'Content-Type' => 'application/yaml'
        ]);
    }

    /**
     * Generate the Swagger UI HTML
     */
    private function getSwaggerHtml()
    {
        $specUrl = url('/api/swagger/spec');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenTrip API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
        .swagger-ui .topbar {
            background-color: #2c3e50;
        }
        .swagger-ui .topbar .download-url-wrapper .select-label {
            color: #fff;
        }
        .swagger-ui .topbar .download-url-wrapper input[type=text] {
            border: 2px solid #34495e;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: '{$specUrl}',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                validatorUrl: null,
                docExpansion: 'list',
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                displayRequestDuration: true,
                tryItOutEnabled: true
            });
        };
    </script>
</body>
</html>
HTML;
    }
}