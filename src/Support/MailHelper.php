<?php

declare(strict_types=1);

namespace LangLearn\Support;

use Resend;
use RuntimeException;

class MailHelper
{
    private Resend\Client|null $mailClient = null;
    private static ?self $mailService = null;
    private const array TEMPLATES = [
        "waitlist-welcome" => __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "Templates" . DIRECTORY_SEPARATOR . "welcome-to-waitlist.html",
    ];
    
    private function __construct() {
        $this->mailClient = Resend::client($_ENV["RESEND_API_KEY"]);
    }

    public static function getEmailClient(): Self
    {
        if (self::$mailService === null) {
            self::$mailService = new Self;
        }

        return self::$mailService;
    }

    // private function getTemplatePath(string $template): string
    // {
    //     return self::TEMPLATES[$template] ?? "";
    // }

    private function getTemplate(string $template): string
    {
        $templatePath = self::TEMPLATES[$template] ?? "";
        $templatePath = str_replace("\\", "/", $templatePath);

        if (file_exists($templatePath)) {
            $file_stream = fopen($templatePath, "r");
            if ($file_stream === false) {
                throw new RuntimeException("Unable to open file stream");
            }

            $template = fread($file_stream, filesize($templatePath));

            fclose($file_stream);

            return $template;
        } else {
            throw new RuntimeException("Unable to find template file");
        }
    }

    private function populateTemplate(string $template, array $data): string
    {
        $template = $this->getTemplate($template);
        $populatedTemplate = $template;

        foreach ($data as $placeholder => $value) {
            $populatedTemplate = str_replace("{{" . $placeholder . "}}", $value, $populatedTemplate);
        }

        return $populatedTemplate;
    }

    /**
     * Sends an email.
     *
     * @param array|string $to - The email address to send to.
     * @param string $template - The email template name ("waitlist-welcome").
     * @param array $vars - Variables for the template:
     *   Eg:
     *   - email: string, recipient's email
     *   - waitlist_url: string, URL to the waitlist page
     *   - year: string, current year
     * @param string $subject - The subject of the email.
     */
    public function send(array|string $to, string $template, array $vars, string $subject = "Test Mail")
    {
        try {
            if (gettype($to) === "string") {
                $to = [$to];
            }

            $populatedTemplate = $this->populateTemplate($template, $vars);

            $this->mailClient->emails->send([
                'from' => 'LangMaster <langmasterng@traction3.com>',
                'to' => $to,
                'subject' => $subject,
                'html' => $populatedTemplate,
                'text' => 'Hi ' . implode(", ", $to) . ',\n\n'
                    . 'Congratulations! You have been approved to join the LangMaster waitlist. We are excited to have you on board and look forward to providing you with an exceptional language learning experience.\n\n'
                    . 'Stay tuned for updates and further instructions as we prepare to launch our platform.\n\n'
                    . 'Best regards,\n'
                    . 'The LangMaster Team',
            ]);
        } catch (\Throwable $th) {
            throw new RuntimeException($th->getMessage());
        }
    }
}
