<?php

declare(strict_types=1);

namespace LangLearn;

use DateTime;
use Doctrine\DBAL\Connection;
use Exception;
use LangLearn\App\Http\Contract\RequestContext;
use LangLearn\App\Http\Controllers\Authentication;
use LangLearn\App\Http\Controllers\Waitlist;
use LangLearn\App\Http\Middleware\BodyParser;
use LangLearn\App\Http\Middleware\Cors;
use LangLearn\App\Http\Middleware\TrustProxies;
use LangLearn\App\Infrastructure\DB\Core as DB;
use LangLearn\App\Http\Routing\Router;
use LangLearn\App\Infrastructure\DI\Container;
use RuntimeException;

class AppFactory
{
    private static ?self $app = null;
    private static ?DB $db = null;
    private static ?RequestContext $request;
    private static ?Container $diContainer;

    private function __construct(private Router $router, DB $db)
    {
        static::$diContainer = new Container();
        static::$diContainer->bind(RequestContext::class, fn () => new RequestContext);
        
        static::$db = $db;
        $this->registerRoutes();
    }

    public static function getDIContainer(): ?Container 
    {
        return static::$diContainer;
    }

    public static function getRequest(): RequestContext
    {
        if (!static::$request) {
            static::$request = static::getDIContainer()->get(RequestContext::class);
        }

        return static::$request;
    }

    protected static function getDB(): ?DB
    {
        return static::$db;
    }

    public static function getDBConection(): ?Connection
    {
        return static::getDB()?->getConnection();
    }

    public static function create(Router $router, DB $db)
    {
        if (!static::$app) {
            static::$app = new AppFactory($router, $db);
        }

        return static::$app;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->router, $name))
            return call_user_func_array([$this->router, $name], $arguments);

        throw new Exception("Method not found");
    }

    public function run()
    {
        try {
            static::$request = static::getDIContainer()->get(RequestContext::class);

            // Proxy Check
            $proxyTrustResponse = (new TrustProxies([], static::$request))->handle();

            if (!isset($proxyTrustResponse['shouldStop']) || (isset($proxyTrustResponse['shouldStop']) && $proxyTrustResponse['shouldStop'])) {
                http_response_code(400);
                throw new RuntimeException("Request Blocked");
            }

            // CORS
            echo (new Cors([
                "http://localhost:3000",
                "http://127.0.0.1:5500",
                "https://client-six-alpha-92.vercel.app/",
                "https://www.thelangmaster.com"
            ]))->handle(function() {
                return (new BodyParser(static::$request))
                    ->handle(fn () => $this->router->resolve($_SERVER["REQUEST_METHOD"], parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH)));
            });

        } catch (Exception $th) {
            if (isset($_ENV["APP_ENV"]) && ($_ENV["APP_ENV"] === "production")) {
                echo json_encode([
                    "status" => "error",
                    "message" => "An error occurred. Please try again later."
                ], JSON_PRETTY_PRINT);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => $th->getMessage(),
                    "trace" => $th->getTraceAsString()
                ], JSON_PRETTY_PRINT);
            }
        } catch (\Throwable $th) {
            if (isset($_ENV["APP_ENV"]) && ($_ENV["APP_ENV"] === "production")) {
                 echo json_encode([
                    "status" => "error",
                    "message" => "An unexpected error occurred."
                ], JSON_PRETTY_PRINT);  
                return;
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => $th->getMessage(),
                    "trace" => $th->getTraceAsString()
                ], JSON_PRETTY_PRINT);
            }
        }
    }
    

    private function registerRoutes(): void
    {
        // -------------------------------------- GET ROUTES -------------------------------------------------------
        $this
            ->router
            ->get("/", fn() => "Hello world")
            ->post("/health", function(){
                return [
                    "status" => "success",
                    "time" => (new DateTime())->format("Y-m-d H:i:s"),
                    "body" => static::$request->getBody()
                ];
        })

            // -------------------------------------- CONTROLLER ROUTES ------------------------------------------------------
            ->registerAttributeRoute(Authentication::class)
            ->registerAttributeRoute(Waitlist::class);
    }
}
