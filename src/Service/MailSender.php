<?php

namespace App\Service;
use PHPMailer\PHPMailer\PHPMailer;

class MailSender
{
    private static $senderEmail = "siret.mailer@gmail.com";
    private static $senderName = "Siret Mailer";
    private static $senderPassword = "ParolaSiret";

    /**
     * Sends mail to an array of targets using Gmail
     *
     * @param array $recipientArray Array of emails (strings) to send to
     * @param string $subject
     * @param string $message
     * @return boolean TRUE if message was sent successfully. FALSE if error.
     */
    public static function sendMail($recipientArray, $subject, $message) {
        $mail = new PHPMailer;

        //Enable SMTP debugging.
        $mail->SMTPDebug = 3;
        //Set PHPMailer to use SMTP.
        $mail->isSMTP();
        //Set SMTP host name
        $mail->Host = "smtp.gmail.com";
        //Set this to true if SMTP host requires authentication to send email
        $mail->SMTPAuth = true;
        //Provide username and password
        $mail->Username = self::$senderEmail;
        $mail->Password = self::$senderPassword;
        //If SMTP requires TLS encryption then set it
        $mail->SMTPSecure = "tls";
        //Set TCP port to connect 
        $mail->Port = 587;

        $mail->From = self::$senderEmail;
        $mail->FromName = self::$senderName;

        foreach ($recipientArray as $recipient) {
            $mail->addAddress($recipient);
        }

        $mail->isHTML(true);

        $mail->Subject = $subject;
        $mail->Body = $message;

        if(!$mail->send()) 
        {
            return "Mailer Error: " . $mail->ErrorInfo;
            #return FALSE;
        } 
        else 
        {
            return "Message has been sent successfully";
            #return TRUE;
        }
    }
}