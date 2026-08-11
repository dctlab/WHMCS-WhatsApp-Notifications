<?php

namespace Dct\HookNotification\Core\WHMCS;

use Dct\HookNotification\Core\NotificationReport\Http\Controllers\WhatsAppChatApiController;
use Dct\HookNotification\Core\Platforms\Botms\Http\Controllers\BotmsWebhookController;
use Dct\HookNotification\Core\Platforms\MetaWhatsApp\Http\Controllers\MetaWebhookController;
use Dct\HookNotification\Core\Shared\Infrastructure\Singleton;

final class ApiHandler extends Singleton {
    /**
     * @var array<string, array<int, string>>
     */
    private readonly array $endpoints;

    public function __construct()
    {
        $this->endpoints = [
            'password/reset' => [
                PasswordResetService::class,
                'run',
            ],
            'whatsapp/webhook' => [
                MetaWebhookController::class,
                'handle',
            ],
            'botms/webhook' => [
                BotmsWebhookController::class,
                'handle',
            ],
            'chat/poll' => [
                WhatsAppChatApiController::class,
                'poll',
            ],
            'chat/send' => [
                WhatsAppChatApiController::class,
                'send',
            ],
            'test-send' => [
                \Dct\HookNotification\Core\TestSend\Http\Controllers\TestSendController::class,
                'send',
            ],
        ];
    }

    public function routeEndpoint(string $endpoint): void
    {
        $urlParts = parse_url($endpoint);
        $path     = $urlParts['path'] ?? $endpoint;

        /** @var array<string, string> $queryParams */
        parse_str($urlParts['query'] ?? '', $queryParams);

        $match = $this->extractRouteParams($path);


        if ($match) {
            [$class, $method] = $this->endpoints[$match['matchedEndpoint']];
            $params           = $match['endpointParams'];

            $params = array_merge($match['endpointParams'], $queryParams, $_GET);

            $classOutput = $this->invoke($class, $method, $params);

            if (is_array($classOutput) && array_key_exists('__raw', $classOutput)) {
                http_response_code($classOutput['__status'] ?? 200);
                Header('Content-Type: text/plain');
                echo (string) $classOutput['__raw'];

                return;
            }

            echo json_encode($classOutput, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

            http_response_code(200);
            Header('Content-Type: application/json');
        } else {
            http_response_code(404);
        }
    }

    /**
     * @param string $received The received endpoint path.
     *
     * @return array{
     *     matchedEndpoint: string,
     *     endpointParams: array<string, string>
     * }|null
     */
    private function extractRouteParams(string $received): ?array
    {
        $endpoints = array_keys($this->endpoints);

        foreach ($endpoints as $endpoint) {
            $pattern = preg_replace_callback('/\{(\w+)\}/', function ($matches) {
                return '(?P<' . $matches[1] . '>[^\/]+)';
            }, $endpoint);

            if (preg_match('#^' . $pattern . '$#', $received, $matches)) {
                $namedParams = [];

                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $namedParams[$key] = $value;
                    }
                }

                return [
                    'matchedEndpoint' => $endpoint,
                    'endpointParams' => $namedParams,
                ];
            }
        }

        return null;
    }

    /**
     * @param  string                $class
     * @param  string                $method
     * @param  array<string, string> $params
     *
     * @return array<int|string, mixed>
     */
    private function invoke(string $class, string $method, array $params): array
    {
        $instance = new $class();

        $reflection    = new \ReflectionMethod($instance, $method);
        $orderedParams = [];

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $params)) {
                $orderedParams[] = $params[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $orderedParams[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException("Missing parameter: $name");
            }
        }

        /** @var array<mixed> $classOutput */
        $classOutput = $reflection->invokeArgs($instance, $orderedParams);

        return $classOutput;
    }

}
