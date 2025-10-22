<?php

declare(strict_types=1);

namespace LangLearn\App\Http\Controllers;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;
use Exception;
use LangLearn\App\Http\Routing\RouteAttribute;
use LangLearn\AppFactory;
use LangLearn\Support\Helpers\Index;
use Doctrine\DBAL\Exception as DBALException;

class Waitlist {
    #[RouteAttribute('/v1/api/waitlist', 'POST')]
    public function addToWaitlist(): array
    {
        if (!Index::isNotEmptyValInArray(AppFactory::getRequest()->getBody(), ["email"])) {
            throw new Exception("Missing required fields, email");
        }

        extract(AppFactory::getRequest()->getBody());

        if (!preg_match(EMAIL_REGEX, $email)) throw new Exception("Invalid Credentials");

        $qry = AppFactory::getDBConection()->prepare("INSERT INTO waitlist (email) VALUES (:email)");

        $qry->bindValue(":email", (string) $email, ParameterType::STRING);

        try {
            $result = $qry->executeStatement();

            if ($result <= 0) {
                throw new Exception("Unable to add email to waitlist");
            };

            return [
                "status"=> "success",
                "message"=> (string) $email . " added to waitlist successfully"
            ];
        } catch (UniqueConstraintViolationException $e) {
            return [
                "status"=> "error",
                "message"=> "Email already in waitlist",
                "code" => 409
            ];
        } catch (DBALException $e) {
            // Generic Doctrine DBAL errors (SQL syntax, connection, etc.)
            return [
                "status" => "error",
                "message" => "Database error: " . $e->getMessage(),
            ];
        }
    }

    #[RouteAttribute("/v1/api/waitlist", "GET")]
    public function getWaitlist() {
        $page = (int) (AppFactory::getRequest()->getQuery()["page"] ?? 1);
        $size = (int) (AppFactory::getRequest()->getQuery()["size"] ?? 10);

        // sanitize
        if ($page < 1) $page = 1;
        if ($size < 1) $size = 10;

        $qry = AppFactory::getDBConection()->prepare("
            SELECT email, created_at, status, source
            FROM waitlist
            ORDER BY created_at DESC, id DESC
            OFFSET :offset 
            LIMIT :limit
        ");

        $offset = ($page-1) * $size;
        $fetchLimit = $size + 1;

        $qry->bindValue(":offset", $offset, ParameterType::INTEGER);
        $qry->bindValue(":limit", $fetchLimit, ParameterType::INTEGER);

        $result = $qry->executeQuery();

        $waitlist = $result->fetchAllAssociative();

        $COUNT = AppFactory::getDBConection()->prepare("SELECT COUNT(*) as total FROM waitlist");
        $countResult = $COUNT->executeQuery();
        $totalCount = $countResult->fetchOne();

        return [
            "status" => "success",
            "message" => "Waitlist fetched successfully",
            "data" => array_slice($waitlist, 0, $size - 1), // return only the requested size
            "hasNext" => count($waitlist) === $fetchLimit,
            "totalCount" => $totalCount
        ];
    }
}