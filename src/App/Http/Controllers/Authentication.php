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

class Authentication
{
    #[RouteAttribute('/v1/api/login', 'POST')]
    public function login(): array
    {
        // Here you would typically handle the login logic, such as validating credentials.
        if (!Index::isNotEmptyValInArray(AppFactory::getRequest()->getBody(), ['email', 'password'])) {
            throw new Exception("Missing required fields: email or password");
        };

        extract(AppFactory::getRequest()->getBody());

        $qry = AppFactory::getDBConection()->prepare("SELECT * FROM users WHERE email = :email ORDER BY created_at DESC LIMIT 1");

        $qry->bindValue(":email", $email, ParameterType::STRING);

        $result = $qry->executeQuery();

        $user = $result->fetchAllAssociative();

        if ((count($user) <= 0) || !password_verify($password, $user[0]['password'])) {
            throw new Exception("Invalid email or password");
        }

        $user = $user[0];

        unset($user["id"], $user["password"], $user["agree_to_terms"]);
        $user["preferred_language"] = strtolower($user["preferred_language"]); // Add remember_me to the user data
        $user["proficiency_level"] = strtolower($user["proficiency_level"]);

        if (isset($remember_me) && $remember_me) {
            $user["remember_me"] = true; // Add remember_me to the user data
        } else {
            $user["remember_me"] = false; // Add remember_me to the user data
            $remember_me = false; // Default to false if not set
        }

        return [
            "status" => "success",
            "message" => "Logged In Successfully",
            "jwt" => Index::generateJwt(["email" => $user], $_ENV["JWT_SECRET"], ((bool) $remember_me) ? (3600 * 24 * 14) : (3600 * 12))
        ];
    }

    #[RouteAttribute('/api/logout', 'POST')]
    public function logout(): array
    {
        // Here you would typically handle the logout logic, such as clearing session data.
        // For now, we return a simple message.
        return [
            "status" => "success",
            "message" => "Logout endpoint reached"
        ];
    }

    #[RouteAttribute('/v1/api/register', 'POST')]
    public function register(): array
    {
        if (!Index::isNotEmptyValInArray(AppFactory::getRequest()->getBody(), ['email', 'password', 'username', 'full_name', 'preferred_language', 'proficiency_level', 'agree_to_terms'])) {
            throw new Exception("Missing required fields: email, password, username, full_name, preferred_language, proficiency_level, or agree_to_terms");
        };

        extract(AppFactory::getRequest()->getBody());

        if (!preg_match(EMAIL_REGEX, $email)) throw new Exception("Invalid Credentials");
        if (!preg_match(PASSWORD_REGEX, $password)) throw new Exception("Invalid Credentials");

        if (strlen($username) < 3 || strlen($username) > 20) throw new Exception("Username must be between 3 and 20 characters long");
        if (strlen($full_name) < 3 || strlen($full_name) > 50) throw new Exception("Full name must be between 3 and 50 characters long");

        if (!in_array($preferred_language, PERMITTED_LANGUAGES)) throw new Exception("Invalid permitted language");
        if (!in_array($proficiency_level, PROFICIENCY_LEVELS)) throw new Exception("Invalid proficiency level");

        if (!((bool) $agree_to_terms)) throw new Exception("You must agree to the terms and conditions");

        try {
            $qry = AppFactory::getDBConection()->prepare("
                INSERT INTO users (
                    email, 
                    password, 
                    username, 
                    full_name, 
                    preferred_language, 
                    proficiency_level, 
                    agree_to_terms
                ) 
                VALUES (
                    :email, 
                    :password, 
                    :username, 
                    :full_name, 
                    :preferred_language, 
                    :proficiency_level,
                    :agree_to_terms
                )
            ");

            $qry->bindValue(":email", $email, ParameterType::STRING);
            $qry->bindValue(":password", password_hash($password, PASSWORD_DEFAULT), ParameterType::STRING);
            $qry->bindValue(":username", $username, ParameterType::STRING);
            $qry->bindValue(":full_name", $full_name, ParameterType::STRING);
            $qry->bindValue(":preferred_language", strtoupper($preferred_language), ParameterType::STRING);
            $qry->bindValue(":proficiency_level", strtoupper($proficiency_level), ParameterType::STRING);
            $qry->bindValue(":agree_to_terms", $agree_to_terms, ParameterType::BOOLEAN);

            $result = $qry->executeStatement();

            if ($result <= 0) {
                throw new \RuntimeException("User not created successfully");
            }

            return [
                "status" => "success",
                "message" => "User registered successfully",
            ];

        } catch (UniqueConstraintViolationException $e) {
            // This one triggers for duplicate email, username, etc.
            return [
                "status" => "error",
                "message" => "Email or username already exists.",
                "code" => 409,
            ];

        } catch (DBALException $e) {
            // Generic Doctrine DBAL errors (SQL syntax, connection, etc.)
            return [
                "status" => "error",
                "message" => "Database error: " . $e->getMessage(),
            ];
        }
    }

    #[RouteAttribute('/v1/api/admin/login', 'POST')]
    public function adminLoginFunction(): array
    {
        $body = AppFactory::getRequest()->getBody();

        if (!Index::isNotEmptyValInArray($body, ['email', 'password'])) {
            return [
                "status"=> "error",
                "message"=> "Missing required fields: email or password",
                "code" => 400,
            ];
        }

        $qry = AppFactory::getDBConection()->prepare("SELECT * FROM admins WHERE email = :email ORDER BY created_at DESC LIMIT 1");
        $qry->bindValue(":email", $body['email'], ParameterType::STRING);

        $result = $qry->executeQuery();

        if ($result->rowCount() === 0) {
            return [
                "status"=> "error",
                "message"=> "Invalid email or password",
                "code" => 401
            ];
        }

        $admin = $result->fetchAllAssociative()[0];
        if (!password_verify($body['password'], $admin['password'])) {
            return [
                "status"=> "error",
                "message"=> "Invalid email or password",
                "code" => 401
            ];
        }

        return [
            "status" => "success",
            "message" => "Admin Logged In Successfully",
            "jwt" => Index::generateJwt(["email" => $admin], $_ENV["JWT_SECRET"], (3600 * 12)),
            "code" => 200
        ];
    }

    #[RouteAttribute("/v1/api/admin/add-admin","POST")]
    public function adminSignupFunction(): array {
        // This is a protected function. Only super admins can create new admin accounts.
        // Implementation of authentication check is assumed to be handled elsewhere.

        // For now, we return a simple success message.
        // In a real implementation, you would add logic to create a new admin account here.
        $body = AppFactory::getRequest()->getBody();

        if (!Index::isNotEmptyValInArray($body, ["email", "password", "full_name", "super_admin_token"])) {
            return [
                "status"=> "error",
                "message"=> "Missing required fields: email, password, full_name, or super_admin_token",
                "code" => 400
            ];
        }

        if ($body['super_admin_token'] !== $_ENV['SUPER_ADMIN_TOKEN']) {
            return [
                "status"=> "error",
                "message"=> "Unauthorized: Invalid super admin token",
                "code" => 403,
            ];
        }

        $qry = AppFactory::getDBConection()->prepare("INSERT INTO admins (email, password, full_name) VALUES (:email, :password, :full_name)");
        $qry->bindValue(":email", $body['email'], ParameterType::STRING);
        $qry->bindValue(":password", password_hash($body['password'], PASSWORD_DEFAULT), ParameterType::STRING);
        $qry->bindValue(":full_name", $body['full_name'], ParameterType::STRING);

        $result = $qry->executeStatement();

        if ($result <= 0) {
            return [
                "status"=> "error",
                "message"=> "Unable to create admin account",
                "code" => 500,
            ];
        }

        return [
            "status" => "success",
            "message" => "Admin account created successfully",
            "code"=> 200
        ];
    }
}
