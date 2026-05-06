<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load the files
require 'libs/Exception.php';
require 'libs/PHPMailer.php';
require 'libs/SMTP.php';

function sendOTP($userEmail, $otpCode) {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();                                            
        $mail->Host       = 'smtp.gmail.com';                     
        $mail->SMTPAuth   = true;                                   
        
        // ==========================================================
        // ✅ YOUR CREDENTIALS (UPDATED)
        // ==========================================================
        $mail->Username   = 'realregards97@gmail.com';  // Your actual email
        $mail->Password   = 'dnsjsakcmhztpctv';         // Your App Password
        // ==========================================================

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         
        $mail->Port       = 587;                                    

        // ----------------------------------------------------------
        // 🔧 THE XAMPP FIX (Bypasses SSL Certificate Check)
        // ----------------------------------------------------------
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        // ----------------------------------------------------------

        //Recipients
        // validation: sender email must match the Username above
        $mail->setFrom('realregards97@gmail.com', 'SamTech Admin Security'); 
        $mail->addAddress($userEmail);                              

        //Content
        $mail->isHTML(true);                                        
        $mail->Subject = 'Your Login Verification Code';
        $mail->Body    = 'Your OTP is: <b>' . $otpCode . '</b>';

        $mail->send();
        return true;
   } catch (Exception $e) {
        // 🛑 COMMENT THIS OUT FOR PRODUCTION so clients don't see ugly errors
        // echo "<b>Mailer Error:</b> " . $mail->ErrorInfo; 
        
        // Just return false so the login page handles the error gracefully
        return false;
    }
    
}
?>